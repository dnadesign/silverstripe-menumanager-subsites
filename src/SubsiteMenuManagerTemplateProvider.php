<?php

namespace Guttmann\SilverStripe;

use Heyday\MenuManager\MenuSet;
use SilverStripe\Subsites\State\SubsiteState;
use SilverStripe\View\TemplateGlobalProvider;

class SubsiteMenuManagerTemplateProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return array(
            'SubsiteMenuSet' => 'SubsiteMenuSet'
        );
    }

    public static function SubsiteMenuSet($name)
    {
        $subsiteID =  SubsiteState::singleton()->getSubsiteId();
        if($subsiteID > 0){
            $name .= '-' . $subsiteID;
        }
        return MenuSet::get()->filter(array(
            'Name' => $name,
            'SubsiteID' => SubsiteState::singleton()->getSubsiteId()
        ))->First();
    }

}
