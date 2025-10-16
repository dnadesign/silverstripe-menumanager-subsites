<?php

namespace Guttmann\SilverStripe;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Core\Extension;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

class MenuItemExtension extends Extension
{
    private static $has_one = [
        'Subsite' => Subsite::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->push(new HiddenField('SubsiteID'));
    }

    public function onBeforeWrite()
    {
        if (!$this->owner->SubsiteID) {
            $this->owner->SubsiteID = SubsiteState::singleton()->getSubsiteId();
        }
    }
}
