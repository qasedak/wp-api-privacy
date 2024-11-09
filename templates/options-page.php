<div class="wrap">
    <h1>WP API Privacy</h1>

    <p><?php _e( 'You can configure the options for API privacy here.', 'wp-api-privacy' ); ?></p>

    <?php $this->doOptionsHeader(); ?>

    <form method="post" action="options-general.php?page=api-privacy"> 
        <input type="hidden" name="wp_api_privacy_settings" value="1">
        <input type="hidden" name="wp_api_privacy_nonce" value="<?php echo wp_create_nonce( 'wpprivacy' ); ?>">
        <table class="form-table" role="presentation">
            <tbody>
                <?php foreach( $this->settingsSections as $name => $data ) { ?>
                    <tr>
                        <th><?php echo esc_html( $data[ 0 ] ); ?></th>
                        <td>
                            <fieldset>
                                <?php foreach( $data[ 1 ] as $setting ) { ?>
                                    <?php $this->renderOneSetting( $setting ); ?>
                                <?php } ?>
                            </fieldset>
                        </td>
                    </tr>
                <?php }?> 
            </tbody>
        </table>
        <input type="submit" id="submit" class="button button-primary" name="submit" value="<?php _e( 'Save Changes', 'wp-api-privacy' ); ?>" />
    </form>
</div>