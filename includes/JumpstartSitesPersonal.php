
<?php
/**
 * @file
 * Stanford Jumpstart Personal Installation Profile.
 *
 *
 */

/**
 * JumpStart Installation Profile Class
 */
class JumpstartSitesPersonal extends JumpstartProfileAbstract {

  /**
   * Required function.
   * These install tasks are run in order from the very first profile (stanford)
   * all the way down to this one. They are run after all the dependency modules
   * have been enabled.
   * @param array $install_state the current state of the installation.
   * @return array an array of installation tasks.
   */
  public function get_install_tasks(&$install_state) {

    // With this line we can choose to use all of the parents install tasks and
    // to run them before this profile. If this line is removed then none of the
    // installation tasks of the parents will be run and you can do whatever you
    // want from there.
    $parent_tasks = parent::get_install_tasks($install_state);


    // Remove some parent tasks.
    // Jumpstart adds content to the site that is different from Stanford Sites Personal. Let's
    // disable those modules and add in only the ones we want again.
    unset($parent_tasks['JumpstartSites_stanford_sites_jumpstart_enable_modules']);

    // Sample task declaration differs from the normal task api slightly.
    $tasks['stanford_sites_jumpstart_personal_install'] = array(
      'display_name' => st('My Profile Install Task'),
      'display' => TRUE,
      'type' => 'normal',
      'function' => 'install', // The name of the method in this class to run.
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    );

    $tasks['stanford_sites_jumpstart_personal_import_content'] = array(
      'display_name' => st('Import Content'),
      'display' => TRUE,
      'type' => 'normal',
      'function' => 'import_content', // The name of the method in this class to run.
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    );

    // Drupal does some fun things to run install tasks so we have to do some
    // extra work to ensure that they are run. Use this function to process your
    // new tasks. Do not pass in parent tasks as they have already been
    // processed once and do not need to be processed again.
    $this->prepare_tasks($tasks, get_class());

    // Return both your and the parent tasks as one array.
    return array_merge($parent_tasks, $tasks);
  }

  /**
   * Required function.
   * This function allows you to maninpulate the installation form as well as
   * the parent profiles to make changes.
   * @param  $form  The form array
   * @param  $form  The form state array
   * @return array the form array
   */
  public function get_config_form(&$form, &$form_state) {

    // Get all parent profile changes and additions to the configuration form.
    $form = parent::get_config_form($form, $form_state);
    return $form;

    // Add your own fields.
    // $form['stanford_sites_jumpstart_sub_example'] = array(
    //   '#type' => 'fieldset',
    //   '#title' => 'My Profile Configuration',
    //   '#description' => 'My Profile Configuration Options.',
    //   '#collapsible' => TRUE,
    //   '#collapsed' => FALSE,
    // );

    // $form['stanford_sites_jumpstart_sub_example']['myvalue'] = array(
    //   '#type' => 'textfield',
    //   '#title' => 'My Configuration Field',
    //   '#description' => 'Please enter some value into this field',
    //   '#default_value' => isset($form_state['values']['myvalue']) ? $form_state['values']['myvalue'] : 'default value',
    // );

    // No need to return anything as this is all passed by reference.
  }

  /**
   * Installation task as defined in get_install_tasks()
   * Sets some configuration
   * @param  array $install_state the current installation state
   * @return null
   */
  public function install(&$install_state) {

    // In here you have full access to everything Drupal. Beware of caches
    // registry and paths as they may not be available or correct. To ensure
    // some normallity it may be useful to flush all the caches first.

    // Set variables.
    $requester_name = variable_get('stanford_sites_requester_name', NULL);
    variable_set('site_name', $requester_name);

    // Variables.
    variable_set('theme_default', 'stanford_light');
    variable_set('admin_theme', 'stanford_seven');
    variable_set('node_admin_theme', 'stanford_seven');
    variable_set('webauth_link_text', "SUNetID Login");
    variable_set('webauth_allow_local', 0);

    // Unset user menu as secondary links.
    variable_set('menu_secondary_links_source', "");

    // Enable themes.
    $themes = array(
      'stanford_framework',
      'stanford_light',
      'stanford_seven',
      'open_framework'
    );

    theme_enable($themes);

    // This is needed here after enabling themes so that blocks get built into
    // the blocks table with the new themes.
    drupal_flush_all_caches();

    // Blocks. Turn em off.
    db_update('block')
    ->fields(array('status' => 0))
    ->condition('module', 'webauth')
    ->condition('delta', 'webauth_login_block')
    ->execute();

    db_update('block')
    ->fields(array('status' => 0))
    ->condition('module', 'system')
    ->condition('delta', 'navigation')
    ->execute();

    db_update('block')
    ->fields(array('status' => 0))
    ->condition('module', 'search')
    ->condition('delta', 'form')
    ->execute();

    db_update('block')
    ->fields(array('status' => 0))
    ->condition('module', 'stanford_sites_helper')
    ->condition('delta', 'firststeps')
    ->execute();

    db_update('block')
    ->fields(array('status' => 0))
    ->condition('module', 'stanford_sites_helper')
    ->condition('delta', 'helplinks')
    ->execute();

    db_update('block')
    ->fields(array('status' => 0))
    ->condition('module', 'user')
    ->condition('delta', 'login')
    ->execute();

  }

  /**
   * Import content from the content server.
   * @param  [type] $install_state [description]
   * @return [type]                [description]
   */
  public function import_content(&$install_state) {

    // Content Server
    $endpoint = 'https://sites.stanford.edu/jsa-content/jsainstall';

    // Try to use libraries module if available to find the path.
    if (function_exists('libraries_get_path')) {
      $library_path = DRUPAL_ROOT . '/' . libraries_get_path('stanford_sites_content_importer');
    }

    if (!drupal_valid_path($library_path)) {
      $library_path = DRUPAL_ROOT . '/sites/all/libraries/stanford_sites_content_importer';
    }

    $library_path .= "/SitesContentImporter.php";
    include_once $library_path;

    $restrict = array(
//      '2efac412-06d7-42b4-bf75-74067879836c',   // Recent News Page
    );

    // $content_types = array(
    //   'stanford_page',
    // );

    // Vocab Pull.
    $importer = new SitesContentImporter();
    $importer->set_endpoint($endpoint);
    $importer->import_vocabulary_trees();

    // Bean Pull.
    $uuids = array(
      '2066e872-9547-40be-9342-dbfb81248589', // Jumpstart Footer Social Media Connect Block
      'd6312ea0-d128-4805-ad0e-fa712aa1ac40', // Stanford Personal Node Edit Help Block
      'a0188c23-cd48-4886-a1a1-15d198e5329d', // Stanford Personal Footer Block
      'd08151ab-2808-4569-9e9a-e977c2ba57c4', // Stanford Personal Sidebar Block
    );

    $importer->set_bean_uuids($uuids);
    $importer->import_content_beans();

    // Content Pull.
    $filters = array('sites_products' => array('37'));
    $view_importer = new SitesContentImporterViews();
    $view_importer->set_endpoint($endpoint);
    $view_importer->set_resource('content');
    $view_importer->set_filters($filters);
    $view_importer->import_content_by_views_and_filters();

  }

}
