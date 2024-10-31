<div class="wrap">
    <h2>JEM PDF Invoices</h2>
    <?php
    if(isset($_POST['jem_pdf'])){
        echo '<div class="updated"><p>'.__('Setting Updated!',JEM_PDFLITE).'</p></div>';
    }
    ?>
    <div class="wrap woocommerce">
        <?php $tab = isset($_GET['tab']) ? $_GET['tab'] : 'general-settings'; ?>
        <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=jem-pdf-pro&tab=general-settings') ?>"
               class="nav-tab <?php echo ($tab == 'general-settings') ? 'nav-tab-active' : ''; ?>"><?php _e('General Settings', JEM_PDFLITE); ?></a>
            <a href="<?php echo admin_url('admin.php?page=jem-pdf-pro&tab=template-settings') ?>"
               class="nav-tab <?php echo ($tab == 'template-settings') ? 'nav-tab-active' : ''; ?>"><?php _e('Header & Footer', JEM_PDFLITE); ?></a>
            <a href="<?php echo admin_url('admin.php?page=jem-pdf-pro&tab=content-settings') ?>"
               class="nav-tab <?php echo ($tab == 'content-settings') ? 'nav-tab-active' : ''; ?>"><?php _e('Detailed Content', JEM_PDFLITE); ?></a>
            <a href="<?php echo admin_url('admin.php?page=jem-pdf-pro&tab=localization-settings') ?>"
               class="nav-tab <?php echo ($tab == 'localization-settings') ? 'nav-tab-active' : ''; ?>"><?php _e('Localization', JEM_PDFLITE); ?></a>
        </h2>
        <?php
        switch ($tab) {
            case "general-settings" :
                Jem_Pdf_Dashboard_Settings::general_settings();
                break;
            case "template-settings" :
                Jem_Pdf_Dashboard_Settings::template_settings();
                break;
            case "content-settings" :
                Jem_Pdf_Dashboard_Settings::content_settings();
                break;
            case "localization-settings" :
                Jem_Pdf_Dashboard_Settings::localization_settings();
                break;
            default :
                Jem_Pdf_Dashboard_Settings::general_settings();
                break;
        }
        ?>
    </div>
</div>
