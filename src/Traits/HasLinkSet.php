<?php

namespace Trov\Linkables\Traits;

use App\Models\LinkSet;

trait HasLinkSet
{
    public function linkSets()
    {
        return $this->morphMany(LinkSet::class, 'linkable');
    }
}
