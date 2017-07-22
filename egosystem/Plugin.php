<?php namespace Code8\EgoSystem;

use Backend;
use System\Classes\PluginBase;

/**
 * EgoSystem Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'EgoSystem',
            'description' => 'Tools for EGO System - suitecrm',
            'author'      => 'Code8',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        // return []; // Remove this line to activate

        return [
            'Code8\EgoSystem\Components\FormContact' => 'egoFormContact',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'code8.egosystem.some_permission' => [
                'tab' => 'EgoSystem',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'egosystem' => [
                'label'       => 'EgoSystem',
                'url'         => Backend::url('code8/egosystem/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['code8.egosystem.*'],
                'order'       => 500,
            ],
        ];
    }

     public function registerMailTemplates()
    {
        return [
            'code8.egosystem:emails.contact' => 'AUTO message envoyer au site',
            'code8.egosystem:emails.confirmation' => 'AUTO message envoyer au client',
        ];
    }

}
