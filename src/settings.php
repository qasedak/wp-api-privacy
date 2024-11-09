<?php
/* 
    Copyright (C) 2024 by Duane Storey - All Rights Reserved
    You may use, distribute and modify this code under the
    terms of the GPLv3 license.
 */

namespace WP_Privacy\WP_API_Privacy;

class Settings {
    // The WordPress settings key
    protected const SETTING_KEY = "wp_api_privacy_settings";
    protected const UPDATED_KEY = "wp_api_privacy_updated";

    protected $settings = null;
    protected $settingsSections = [];

    public function __construct() {
        $this->loadSettings();
    }
    
    public function init() {
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'setupSettingsPage' ) );

            $this->processSubmittedSettings();
        }

        $this->addSettingsSection( 
            'options', 
            __( 'Options', 'wp-api-privacy' ),
           array(
                $this->addSetting( 'checkbox', 'strip_user_agent', __( 'Strip site URL from User-Agent header on all requests', 'wp-api-privacy' ) ),
                $this->addSetting( 'checkbox', 'strip_plugins', __( 'Strip external plugins from API calls', 'wp-api-privacy' ) ),
                $this->addSetting( 'checkbox', 'strip_themes', __( 'Strip external themes from API calls', 'wp-api-privacy' ) ),
                $this->addSetting( 'checkbox', 'strip_core_data', __( 'Modify data sent to core update API', 'wp-api-privacy' ) ),
                $this->addSetting( 'checkbox', 'strip_core_headers', __( 'Strip wp_blog and wp_install headers', 'wp-api-privacy' ) ),
                $this->addSetting( 'checkbox', 'strip_user_logins', __( 'Strip user login info from JSON API', 'wp-api-privacy' ) )
           )
        );

        $this->addSettingsSection( 
            'debug', 
            __( 'Debug', 'wp-api-privacy' ),
           array(
                $this->addSetting( 'checkbox', 'disable_https', __( 'Disable HTTPs for packet sniffing (should only be used for testing)', 'wp-api-privacy' ) ),
           )
        );
    }

    public function doOptionsHeader() {
        if ( get_option( Settings::UPDATED_KEY, false ) ) {
            echo '<div class="notice notice-success settings-error is-dismissible"><p>' . esc_html( __( 'Your settings have been saved', 'wp-api-privacy' ) ) . '</p></div>';
            delete_option( Settings::UPDATED_KEY );
        }
    }

    public function processSubmittedSettings() {
        // These are our settings  
        if ( isset( $_POST[ 'wp_api_privacy_settings' ] ) ) {
            $nonce = $_POST[ 'wp_api_privacy_nonce' ];
            if ( wp_verify_nonce( $nonce, 'wpprivacy' ) && current_user_can( 'manage_options' ) ) {
                // get a list of submitted settings that don't include our hidden fields
                $defaultSettings = $this->getDefaultSettings();
                foreach( $defaultSettings as $name => $dontNeed ) {
                    if ( isset( $_POST[ 'wpcheckbox_' . $name ] ) ) {
                        // this is a checkbox
                        if ( isset( $_POST[ 'wpsetting_' . $name ] ) ) {
                            $this->settings->$name = true;
                        } else {
                            $this->settings->$name = false;
                        }
                    } else {
                        // not a checkbox
                    }
                }

                // Settings are saved, show notification on next page
                update_option( Settings::UPDATED_KEY, 1, false );
                $this->saveSettings();
            }
        }
    }

    public function saveSettings() {
        update_option( Settings::SETTING_KEY, $this->settings, false );
    }

    public function loadSettings() {
        $settings = get_option( Settings::SETTING_KEY );
        if ( $settings ) {
            $defaults = $this->getDefaultSettings();

            // merge in defaults to ensure new settings are added to old
            foreach( $defaults as $key => $value ) {
                if ( !isset( $settings->$key ) ) {
                    $settings->$key = $defaults->$key;
                }
            }
            // update merged settings
            $this->settings = $settings;
        } else {
            $this->settings = $this->getDefaultSettings();
        }
    }

    public function addSettingsSection( $section, $desc, $settings ) {
        $this->settingsSections[ $section ] = [ $desc, $settings  ];
    }

    public function addSetting( $settingType, $settingName, $settingDesc ) {
        $setting = new \stdClass;
        $setting->type = $settingType;
        $setting->name = $settingName;
        $setting->desc = $settingDesc;

        return $setting;
    }

    public function getSetting( $name ) {
        return $this->settings->$name;
    }

    public function setSetting( $name, $value ) {
        $this->settings->$name = $value;
    }

    public function renderOneSetting( $setting ) {
        switch( $setting->type ) {
            case 'checkbox':
                $checked = ( $this->getSetting( $setting->name ) ? ' checked' : '' );
                echo '<label for="' . esc_attr( $setting->name ) . '">';
                echo '<input type="checkbox" name="wpsetting_' . esc_attr( $setting->name ) . '" ' . $checked . '/> ';
                echo '<input type="hidden" name="wpcheckbox_' . esc_attr( $setting->name ) . '" value="1" />';
                echo esc_html( $setting->desc ) . '</label>';
                echo "<br>";
                break;
        }
    }

    public function getDefaultSettings() {
        $settings = new \stdClass;

        // Adding default settings
        $settings->strip_user_agent = true;
        $settings->strip_plugins = true;
        $settings->strip_themes = true;
        $settings->strip_core_data = true;
        $settings->strip_core_headers = true;
        $settings->strip_user_logins = true;
        $settings->disable_https = false;

        $settings->modification_count = 0;

        return $settings;
    }

    public function renderSettingsPage() {
        require_once( PRIVACY_PATH . '/templates/options-page.php' );
    }

    public function setupSettingsPage() {
        add_options_page(
            __( 'WP API Privacy', 'wp-api-privacy' ),
            __( 'WP API Privacy', 'wp-api-privacy' ),
            'manage_options',
            'api-privacy',
            array( $this, 'renderSettingsPage' )
        );   
    }
}