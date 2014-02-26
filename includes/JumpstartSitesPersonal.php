
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
class JumpstartSitesPersonal extends JumpstartSites {

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
      '2efac412-06d7-42b4-bf75-74067879836c',   // Recent News Page
    );

    $content_types = array(
      'stanford_page',
    );

    // Content Pull.
    $importer = new SitesContentImporter();
    $importer->set_endpoint($endpoint);
    $importer->import_vocabulary_trees();

    // Content Pull.
    $importer->add_import_content_type($content_types);
    $importer->add_uuid_restrictions($restrict);
    $importer->importer_content_nodes_recent_by_type();


  }

}
