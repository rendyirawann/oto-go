<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \App\Models\Post */
class PostDetailResource extends PostSummaryResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'content_html' => $this->renderedContent()['html'],
            'author_name' => $this->whenLoaded('author', fn () => $this->author?->name, null) ?? 'Tim OtoGo',
            'related' => PostSummaryResource::collection($this->related(3)),
        ]);
    }
}
