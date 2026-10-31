/*
 * OtoGo AI assistant: floating widget + full page.
 *
 * Model output is untrusted. Every string is HTML-escaped first; only then is
 * a small markdown subset turned into tags we generate ourselves. Links are
 * restricted to http(s). No inline handlers, no eval - CSP friendly.
 */
(function () {
    'use strict';

    if (window.__otogoAi) {
        return;
    }
    window.__otogoAi = true;

    var TOOL_LABELS = {
        price_estimate: 'Tarif Medan',
        web_search: 'Web',
        blog_search: 'Blog OtoGo',
        platform_overview: 'Data platform'
    };

    // ------------------------------------------------------------ helpers

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function storageGet(key) {
        try { return key ? window.sessionStorage.getItem(key) : null; } catch (e) { return null; }
    }

    function storageSet(key, value) {
        try {
            if (!key) { return; }
            if (value) { window.sessionStorage.setItem(key, value); } else { window.sessionStorage.removeItem(key); }
        } catch (e) { /* storage disabled */ }
    }

    // Inline markdown on an already-escaped string.
    function inline(escaped) {
        // Generated tags (code, links) are swapped for placeholders so the
        // bold/italic pass can never touch their attributes or URLs.
        var tokens = [];
        function hold(html) {
            tokens.push(html);
            return '\u0000' + (tokens.length - 1) + '\u0000';
        }
        function link(url, label) {
            // URL is already HTML-escaped, so it cannot break out of the attribute.
            return hold('<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + label + '</a>');
        }

        escaped = escaped.replace(/`([^`\n]+)`/g, function (_, code) {
            return hold('<code>' + code + '</code>');
        });

        escaped = escaped.replace(/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/gi, function (_, label, url) {
            return link(url, label);
        });

        escaped = escaped.replace(/(^|[\s(])(https?:\/\/[^\s<)]+?)([.,;:!?]?)(?=$|[\s)])/gi, function (_, lead, url, trail) {
            return lead + link(url, url) + trail;
        });

        escaped = escaped
            .replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>')
            .replace(/__([^_\n]+)__/g, '<strong>$1</strong>')
            .replace(/(^|[^*\w])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
            .replace(/(^|[^_\w])_([^_\n]+)_(?!_)/g, '$1<em>$2</em>');

        // Restore (repeat in case a link label held a code placeholder).
        for (var pass = 0; pass < 2; pass++) {
            escaped = escaped.replace(/\u0000(\d+)\u0000/g, function (_, i) { return tokens[+i]; });
        }
        return escaped;
    }

    function isTableRow(line) {
        return /^\s*\|.*\|\s*$/.test(line);
    }

    function isTableDivider(line) {
        return /^\s*\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?\s*$/.test(line);
    }

    function cells(line) {
        return line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map(function (c) {
            return inline(escapeHtml(c.trim()));
        });
    }

    function renderMarkdown(source) {
        var lines = String(source || '').replace(/\r\n?/g, '\n').split('\n');
        var html = [];
        var paragraph = [];
        var i = 0;

        function flush() {
            if (paragraph.length) {
                html.push('<p>' + paragraph.map(function (l) { return inline(escapeHtml(l)); }).join('<br>') + '</p>');
                paragraph = [];
            }
        }

        while (i < lines.length) {
            var line = lines[i];

            // Fenced code block
            if (/^\s*```/.test(line)) {
                flush();
                var code = [];
                i++;
                while (i < lines.length && !/^\s*```/.test(lines[i])) {
                    code.push(lines[i]);
                    i++;
                }
                i++;
                html.push('<pre><code>' + escapeHtml(code.join('\n')) + '</code></pre>');
                continue;
            }

            // Table: header row + divider
            if (isTableRow(line) && i + 1 < lines.length && isTableDivider(lines[i + 1])) {
                flush();
                var head = cells(line);
                var rows = [];
                i += 2;
                while (i < lines.length && isTableRow(lines[i])) {
                    rows.push(cells(lines[i]));
                    i++;
                }
                html.push(
                    '<div class="ai-md__table"><table><thead><tr>' +
                    head.map(function (c) { return '<th>' + c + '</th>'; }).join('') +
                    '</tr></thead><tbody>' +
                    rows.map(function (r) { return '<tr>' + r.map(function (c) { return '<td>' + c + '</td>'; }).join('') + '</tr>'; }).join('') +
                    '</tbody></table></div>'
                );
                continue;
            }

            // Lists
            var bullet = /^\s*[-*•]\s+(.*)$/;
            var number = /^\s*\d+[.)]\s+(.*)$/;
            if (bullet.test(line) || number.test(line)) {
                flush();
                var ordered = number.test(line) && !bullet.test(line);
                var pattern = ordered ? number : bullet;
                var items = [];
                while (i < lines.length && pattern.test(lines[i])) {
                    items.push('<li>' + inline(escapeHtml(lines[i].replace(pattern, '$1'))) + '</li>');
                    i++;
                }
                html.push((ordered ? '<ol>' : '<ul>') + items.join('') + (ordered ? '</ol>' : '</ul>'));
                continue;
            }

            // Headings -> calm emphasised line
            var heading = /^\s*#{1,6}\s+(.*)$/.exec(line);
            if (heading) {
                flush();
                html.push('<p class="ai-md__h">' + inline(escapeHtml(heading[1])) + '</p>');
                i++;
                continue;
            }

            // Horizontal rule
            if (/^\s*(-{3,}|\*{3,})\s*$/.test(line)) {
                flush();
                i++;
                continue;
            }

            if (line.trim() === '') {
                flush();
            } else {
                paragraph.push(line);
            }
            i++;
        }

        flush();
        return html.join('');
    }

    // --------------------------------------------------------------- chat

    function Chat(root) {
        this.root = root;
        this.endpoint = root.getAttribute('data-endpoint');
        this.conversationUrl = root.getAttribute('data-conversation-url');
        this.avatar = root.getAttribute('data-avatar');
        this.storageKey = root.getAttribute('data-storage-key');
        this.thread = root.querySelector('[data-ai-thread]');
        this.empty = root.querySelector('[data-ai-empty]');
        this.form = root.querySelector('[data-ai-form]');
        this.input = root.querySelector('[data-ai-input]');
        this.send = root.querySelector('[data-ai-send]');
        this.pending = false;
        this.conversationId = storageGet(this.storageKey);
        this.listeners = [];

        this.bind();

        if (this.conversationId && this.storageKey) {
            this.load(this.conversationId, true);
        }
    }

    Chat.prototype.on = function (fn) {
        this.listeners.push(fn);
    };

    Chat.prototype.emit = function (event, data) {
        this.listeners.forEach(function (fn) { fn(event, data); });
    };

    Chat.prototype.bind = function () {
        var self = this;

        this.form.addEventListener('submit', function (e) {
            e.preventDefault();
            self.submit();
        });

        this.input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                self.submit();
            }
        });

        this.input.addEventListener('input', function () {
            self.autosize();
            self.syncButton();
        });

        this.root.querySelectorAll('[data-ai-suggest]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                self.input.value = chip.textContent.trim();
                self.submit();
            });
        });

        var reset = this.root.querySelector('[data-ai-new]');
        if (reset) {
            reset.addEventListener('click', function () {
                self.reset();
                self.emit('reset');
                self.input.focus();
            });
        }
    };

    Chat.prototype.autosize = function () {
        this.input.style.height = 'auto';
        this.input.style.height = Math.min(this.input.scrollHeight, 160) + 'px';
    };

    Chat.prototype.syncButton = function () {
        this.send.disabled = this.pending || this.input.value.trim() === '';
    };

    Chat.prototype.scrollToEnd = function () {
        this.thread.scrollTop = this.thread.scrollHeight;
    };

    Chat.prototype.hideEmpty = function () {
        if (this.empty) { this.empty.hidden = true; }
    };

    Chat.prototype.clearThread = function () {
        var self = this;
        Array.prototype.slice.call(this.thread.children).forEach(function (child) {
            if (child !== self.empty) { child.remove(); }
        });
        if (this.empty) { this.empty.hidden = false; }
    };

    Chat.prototype.reset = function () {
        this.conversationId = null;
        storageSet(this.storageKey, null);
        this.clearThread();
    };

    Chat.prototype.addUser = function (text) {
        this.hideEmpty();
        var row = document.createElement('div');
        row.className = 'ai-msg ai-msg--user';
        var body = document.createElement('div');
        body.className = 'ai-msg__body';
        body.textContent = text;
        row.appendChild(body);
        this.thread.appendChild(row);
        this.scrollToEnd();
    };

    Chat.prototype.assistantShell = function (extraClass) {
        var row = document.createElement('div');
        row.className = 'ai-msg ai-msg--assistant' + (extraClass ? ' ' + extraClass : '');

        var avatar = document.createElement('img');
        avatar.className = 'ai-msg__avatar';
        avatar.src = this.avatar;
        avatar.alt = '';
        avatar.width = 26;
        avatar.height = 26;

        var content = document.createElement('div');
        content.className = 'ai-msg__content';

        var body = document.createElement('div');
        body.className = 'ai-msg__body ai-md';

        content.appendChild(body);
        row.appendChild(avatar);
        row.appendChild(content);
        return { row: row, content: content, body: body };
    };

    Chat.prototype.addAssistant = function (markdown, sources, tools) {
        this.hideEmpty();
        var shell = this.assistantShell();
        // Safe: renderMarkdown escapes everything before adding its own tags.
        shell.body.innerHTML = renderMarkdown(markdown);

        if (tools && tools.length) {
            var tags = document.createElement('div');
            tags.className = 'ai-msg__tags';
            tools.forEach(function (tool) {
                if (!TOOL_LABELS[tool]) { return; }
                var tag = document.createElement('span');
                tag.className = 'ai-tag';
                tag.textContent = TOOL_LABELS[tool];
                tags.appendChild(tag);
            });
            if (tags.children.length) { shell.content.appendChild(tags); }
        }

        if (sources && sources.length) {
            var list = document.createElement('ul');
            list.className = 'ai-sources';
            var label = document.createElement('li');
            label.className = 'ai-sources__label';
            label.textContent = 'Sumber';
            list.appendChild(label);

            sources.forEach(function (source) {
                if (!source || !/^https?:\/\//i.test(source.url || '')) { return; }
                var item = document.createElement('li');
                var link = document.createElement('a');
                link.href = source.url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = source.title || source.url;
                link.title = source.url;
                item.appendChild(link);
                list.appendChild(item);
            });
            shell.content.appendChild(list);
        }

        this.thread.appendChild(shell.row);
        this.scrollToEnd();
    };

    Chat.prototype.addError = function (text) {
        var shell = this.assistantShell('ai-msg--error');
        shell.body.textContent = text;
        this.thread.appendChild(shell.row);
        this.scrollToEnd();
    };

    Chat.prototype.showTyping = function () {
        var shell = this.assistantShell('ai-msg--typing');
        shell.body.innerHTML = '<span class="ai-typing" aria-label="Asisten sedang mengetik"><span></span><span></span><span></span></span>';
        this.thread.appendChild(shell.row);
        this.scrollToEnd();
        return shell.row;
    };

    Chat.prototype.setConversation = function (id, title) {
        this.conversationId = id || null;
        storageSet(this.storageKey, this.conversationId);
        if (id) { this.emit('conversation', { id: id, title: title }); }
    };

    Chat.prototype.submit = function () {
        var text = this.input.value.trim();
        if (!text || this.pending) { return; }

        var self = this;
        this.pending = true;
        this.input.value = '';
        this.autosize();
        this.syncButton();
        this.addUser(text);
        var typing = this.showTyping();

        fetch(this.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf()
            },
            body: JSON.stringify({ message: text, conversation_id: this.conversationId })
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (json) {
                    return { status: response.status, json: json };
                });
            })
            .then(function (res) {
                typing.remove();
                var json = res.json || {};

                if (res.status === 200 && json.ok) {
                    self.setConversation(json.data.conversation_id, json.data.conversation_title);
                    self.addAssistant(json.data.reply, json.data.sources, json.data.tools);
                    return;
                }

                if (res.status === 200 && json.data && json.data.conversation_id) {
                    self.setConversation(json.data.conversation_id);
                }

                self.addError(self.errorMessage(res.status, json));
            })
            .catch(function () {
                typing.remove();
                self.addError('Tidak dapat terhubung ke server. Periksa koneksi Anda lalu coba lagi.');
            })
            .then(function () {
                self.pending = false;
                self.syncButton();
            });
    };

    Chat.prototype.errorMessage = function (status, json) {
        if (status === 419) { return 'Sesi halaman sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.'; }
        if (status === 401) { return 'Sesi login berakhir. Silakan masuk kembali.'; }
        if (status === 429) { return 'Terlalu banyak pertanyaan dalam waktu singkat. Tunggu sebentar lalu coba lagi.'; }
        if (status === 422 && json.errors) {
            var first = Object.keys(json.errors)[0];
            if (first && json.errors[first][0]) { return json.errors[first][0]; }
        }
        return json.message || 'Asisten AI sedang tidak tersedia. Coba lagi sebentar lagi.';
    };

    Chat.prototype.load = function (id, quiet) {
        var self = this;
        if (!id || !this.conversationUrl) { return Promise.resolve(); }

        return fetch(this.conversationUrl.replace('__ID__', encodeURIComponent(id)), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) { throw new Error(String(response.status)); }
                return response.json();
            })
            .then(function (json) {
                self.clearThread();
                self.conversationId = json.data.id;
                storageSet(self.storageKey, self.conversationId);
                (json.data.messages || []).forEach(function (m) {
                    if (m.role === 'user') { self.addUser(m.content); } else { self.addAssistant(m.content, m.sources); }
                });
                self.emit('loaded', { id: json.data.id });
            })
            .catch(function () {
                // Stale id (deleted elsewhere): start fresh instead of showing an error.
                self.reset();
                if (!quiet) { self.addError('Percakapan tidak ditemukan.'); }
            });
    };

    // ------------------------------------------------------------- widget

    function initWidget(widget) {
        var launcher = widget.querySelector('[data-ai-launcher]');
        var panel = widget.querySelector('.ai-panel');
        var root = widget.querySelector('[data-ai-chat]');
        if (!launcher || !panel || !root) { return; }

        var chat = null;

        function open() {
            if (!chat) { chat = new Chat(root); }
            panel.hidden = false;
            widget.classList.add('is-open');
            launcher.setAttribute('aria-expanded', 'true');
            window.setTimeout(function () { chat.input.focus(); }, 30);
        }

        function close() {
            panel.hidden = true;
            widget.classList.remove('is-open');
            launcher.setAttribute('aria-expanded', 'false');
            launcher.focus();
        }

        launcher.addEventListener('click', function () {
            if (panel.hidden) { open(); } else { close(); }
        });

        var closeBtn = root.querySelector('[data-ai-close]');
        if (closeBtn) { closeBtn.addEventListener('click', close); }

        panel.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                close();
            }
        });
    }

    // --------------------------------------------------------------- page

    function initPage(page) {
        var root = page.querySelector('[data-ai-chat]');
        var list = page.querySelector('[data-ai-convs]');
        if (!root || !list) { return; }

        var chat = new Chat(root);
        var deleteUrl = page.getAttribute('data-delete-url');

        function setActive(id) {
            list.querySelectorAll('.ai-convs__item').forEach(function (item) {
                item.classList.toggle('is-active', item.getAttribute('data-id') === id);
            });
        }

        function ensureItem(id, title) {
            var item = list.querySelector('.ai-convs__item[data-id="' + CSS.escape(id) + '"]');
            var empty = list.querySelector('[data-ai-convs-empty]');
            if (empty) { empty.remove(); }

            if (!item) {
                item = document.createElement('li');
                item.className = 'ai-convs__item';
                item.setAttribute('data-id', id);

                var openBtn = document.createElement('button');
                openBtn.type = 'button';
                openBtn.className = 'ai-convs__open';
                openBtn.setAttribute('data-ai-open', id);

                var t = document.createElement('span');
                t.className = 'ai-convs__title';
                t.textContent = title || 'Percakapan baru';
                var meta = document.createElement('span');
                meta.className = 'ai-convs__meta';
                meta.textContent = 'baru saja';
                openBtn.appendChild(t);
                openBtn.appendChild(meta);

                var del = document.createElement('button');
                del.type = 'button';
                del.className = 'ai-convs__delete';
                del.setAttribute('data-ai-delete', id);
                del.setAttribute('aria-label', 'Hapus percakapan');
                del.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2M7 7l1 12h8l1-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

                item.appendChild(openBtn);
                item.appendChild(del);
            }

            list.insertBefore(item, list.firstChild);
            setActive(id);
        }

        function confirmDelete() {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    title: 'Hapus percakapan?',
                    text: 'Riwayat percakapan ini akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }
                }).then(function (r) { return !!r.isConfirmed; });
            }
            return Promise.resolve(window.confirm('Hapus percakapan ini?'));
        }

        list.addEventListener('click', function (e) {
            var openBtn = e.target.closest('[data-ai-open]');
            var delBtn = e.target.closest('[data-ai-delete]');

            if (openBtn) {
                var id = openBtn.getAttribute('data-ai-open');
                setActive(id);
                chat.load(id);
                return;
            }

            if (delBtn) {
                var delId = delBtn.getAttribute('data-ai-delete');
                confirmDelete().then(function (ok) {
                    if (!ok) { return; }
                    fetch(deleteUrl.replace('__ID__', encodeURIComponent(delId)), {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() }
                    }).then(function (response) {
                        if (!response.ok) { throw new Error(String(response.status)); }
                        var item = delBtn.closest('.ai-convs__item');
                        if (item) { item.remove(); }
                        if (chat.conversationId === delId) { chat.reset(); }
                        if (!list.querySelector('.ai-convs__item')) {
                            var empty = document.createElement('li');
                            empty.className = 'ai-convs__empty';
                            empty.setAttribute('data-ai-convs-empty', '');
                            empty.textContent = 'Belum ada percakapan.';
                            list.appendChild(empty);
                        }
                    }).catch(function () {
                        chat.addError('Gagal menghapus percakapan. Coba lagi.');
                    });
                });
            }
        });

        var newBtn = page.querySelector('[data-ai-page-new]');
        if (newBtn) {
            newBtn.addEventListener('click', function () {
                chat.reset();
                setActive(null);
                chat.input.focus();
            });
        }

        chat.on(function (event, data) {
            if (event === 'conversation') { ensureItem(data.id, data.title); }
            if (event === 'loaded') { setActive(data.id); }
            if (event === 'reset') { setActive(null); }
        });

        // Open the most recent conversation if there is one.
        var first = list.querySelector('[data-ai-open]');
        if (first) {
            var firstId = first.getAttribute('data-ai-open');
            setActive(firstId);
            chat.load(firstId, true);
        }
    }

    function boot() {
        document.querySelectorAll('[data-ai-widget]').forEach(initWidget);
        document.querySelectorAll('[data-ai-page]').forEach(initPage);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Exposed for tests / debugging only.
    window.OtoGoAi = { renderMarkdown: renderMarkdown };
})();
