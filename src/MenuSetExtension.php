<?php

namespace Guttmann\SilverStripe;

use Heyday\MenuManager\MenuSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

class MenuSetExtension extends Extension
{
    private static $has_one = [
        'Subsite' => Subsite::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->push(HiddenField::create('SubsiteID'));
    }

    public function onBeforeWrite()
    {
        if (!$this->getOwner()->SubsiteID) {
            $this->getOwner()->SubsiteID = SubsiteState::singleton()->getSubsiteId();
        }
    }

    /**
     * @param mixed $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        $existing = SubsiteMenuManagerTemplateProvider::SubsiteMenuSet($this->getOwner()->Name);
        $isDuplicate = $existing && $existing->ID !== $this->getOwner()->ID;

        if (!$isDuplicate) {
            $defaultSets = $this->getOwner()->config()->get('default_sets');
            $subsiteID =  SubsiteState::singleton()->getSubsiteId();
            if ($subsiteID > 0 && is_array($defaultSets)) {
                foreach ($defaultSets as $defaultSet) {
                    $defaultSubsiteSetName = $defaultSet . '-' . $subsiteID;
                    if ($this->getOwner()->Name === $defaultSubsiteSetName) {
                        return false;
                    }
                }
            }
        }

        return Permission::check('MANAGE_MENU_SETS');
    }

    public function onRequireDefaultRecords()
    {
        $subsites = Subsite::all_sites();
        $defaultSetNames = $this->getOwner()->config()->get('default_sets') ?: [];
        $subsites->each(function ($subsite) use ($defaultSetNames) {
            Subsite::changeSubsite($subsite->ID);

            if ($subsite->ID > 0) {
                foreach ($defaultSetNames as $name) {
                    $name = $name . '-' . $subsite->ID;
                    $existingRecord = MenuSet::get()->filter([
                        'Name' => $name,
                        'SubsiteID' => $subsite->ID,
                    ])->first();

                    if (!$existingRecord) {
                        $set = MenuSet::create();
                        $set->Name = $name;
                        $set->write();

                        DB::alteration_message("MenuSet '$name' created for Subsite", 'created');
                    }
                }
            }
        });
    }
}
