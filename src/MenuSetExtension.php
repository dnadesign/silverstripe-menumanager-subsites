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
    private static $has_one = array(
        'Subsite' => Subsite::class
    );

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

    /**
     * @param mixed $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        $canDelete = parent::canDelete($member);

        $existing = SubsiteMenuManagerTemplateProvider::SubsiteMenuSet($this->owner->Name);
        $isDuplicate = $existing && $existing->ID !== $this->owner->ID;

        if (!$isDuplicate) {
            $defaultSets = $this->owner->config()->get('default_sets');
            $subsiteID =  SubsiteState::singleton()->getSubsiteId();
            if($subsiteID > 0){
                foreach ($defaultSets as $defaultSet) {
                    $defaultSubsiteSetName = $defaultSet . '-' . $subsiteID;
                    if($this->owner->Name === $defaultSubsiteSetName){
                        return false;
                    }
                }
            }
        }

        if ($canDelete !== null) {
            return $canDelete;
        }

        return Permission::check('MANAGE_MENU_SETS');
    }

    public function requireDefaultRecords()
    {
        $subsites = Subsite::all_sites();
        $defaultSetNames = $this->owner->config()->get('default_sets') ?: array();
        $subsites->each(function ($subsite) use ($defaultSetNames) {
            Subsite::changeSubsite($subsite->ID);

            if($subsite->ID > 0){
                foreach ($defaultSetNames as $name) {
                    $name = $name . '-' . $subsite->ID;
                    $existingRecord = MenuSet::get()->filter([
                        'Name' => $name,
                        'SubsiteID' => $subsite->ID,
                    ])->first();
    
                    if (!$existingRecord) {
                        $set = new MenuSet();
                        $set->Name = $name;
                        $set->write();
    
                        DB::alteration_message("MenuSet '$name' created for Subsite", 'created');
                    }
                }
            }
        });
    }
    
}
