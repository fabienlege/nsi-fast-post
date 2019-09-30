<?php
/*
Plugin Name: NSI Fast Post
Plugin URI: https://agenceho5.com
Description: Permet la création rapide de posts wordpress
Version: 2.0
Author: Fabien LEGE | NSI - Agence ho5
Author URI: https://agenceho5.com
*/

class NsiFastPost
{
  protected $slug = 'nsi_fast_post';

  public function __construct(){
    add_action('admin_menu', [$this, 'createMenu'], 5);
    add_action('wp_ajax_'.$this->slug, array($this,'ajax_handler'));
    add_action('admin_footer',array($this,'ajax_script'));
  }

  /**
   * Ajoute l'item au menu du backoffice wordpress
   */
  public function createMenu(){
    $hookname = add_submenu_page('tools.php', 'Génération rapide de post', 'Génération de posts', 'edit_pages', $this->slug, [$this, 'indexPage'] );
    //add_action( 'load-' . $hookname, [$this,'submitForm'] );
  }

  public function indexPage(){
    global $wp_post_types, $wp_post_statuses;
    $post_types = array_filter( $wp_post_types, function($el){ return !in_array($el->name, ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'acf-field']); } );
    $post_statuses = array_filter( $wp_post_statuses, function($key){ return !in_array($key, ['future', 'auto-draft', 'inherit']); }, ARRAY_FILTER_USE_KEY );
    
    ?>
    <div class="wrap">
      <h1>Génération rapide de post</h1>
      <p>Pour générer rapidement vos posts, remplissez le formulaire ci-dessous.</p>
      <form id="nsi_fast_post_form">
        <?php settings_fields( $this->slug ); ?>
        <div class="form-field">
          <label for="post_type">Type de post à créer</label>
          <select id="post_type" name="post_type">
            <?php foreach($post_types as $key => $post_type): ?>
              <option value="<?= $key ?>" <?php if(isset($_GET['post_type']) && $_POST['post_type'] == $key){ echo 'selected="selected"'; } ?>><?= $post_type->label ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <p class="form-field">
          <label for="post_status">Status des posts à créer</label>
          <select id="post_status" name="post_status" >
          <?php foreach($post_statuses as $key => $post_status): ?>
              <option value="<?= $key ?>"><?= $post_status->label ?></option>
            <?php endforeach ?>
          </select>
        </p>
        <p class="form-field">
          <input type="checkbox" id="post_content" name="post_content" checked="checked">
          <label for="post_content">Générer du contenu fictif pour ce post (Lorem Ipsum)</label>
        </p>
        <p class="form-field">
          <label for="post_titles">Titre des posts à créer</label><br>
          <i>Un titre par ligne</i>
          <textarea id="post_titles" name="post_titles" rows="8"></textarea>
        </p>
        <p class="form-field">
          <button class="button button-primary">Créer tous les posts</button>
        </p>
      </form>
    </div>
    <?php
  }
  public function ajax_handler(){
    if(!empty($_POST['post_titles']) && wp_verify_nonce($_POST['security'], $this->slug) ){
      $posts = preg_split('/\r\n|[\r\n]/', $_POST['post_titles']);
      $post_type = $_POST['post_type'];
      $post_status = $_POST['post_status'];
      $post_content= null;
      if($_POST['post_content']){
        $post_content = file_get_contents('http://loripsum.net/api');
      }
      /*var_dump($post_type);
      var_dump($post_status);
      var_dump($posts);*/
      foreach($posts as $post_title){
        wp_insert_post([
          'post_type'       => $post_type,
          'post_status'     => $post_status,
          'post_title'      => $post_title,
          'post_content'    => $post_content,
        ]);
      }
      return 1;
    }
  }

  public function ajax_script() {
    $nonce = wp_create_nonce($this->slug);
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
    $('#nsi_fast_post_form').submit(function(e){
        var input = $(this).closest('form').serialize(),
      parent = $(this).parent(),
            loading = $('.ajax-feedback',parent);
        if( loading.css('visibility') !== 'visible' ) {
            $('div.updated',parent).remove();
            loading.css('visibility','visible');
            $.ajax({
              type: 'POST',
              url: ajaxurl,
              data: input + '&action=<?= $this->slug ?>&security=<?= $nonce ?>',
              dataType: 'json',
              timeout : 30000,
              success: function(data, textStatus, jqXHR) {
                $('<div class="updated" style="display: block !important"><p><strong>Posts créés</strong><br>Les posts que vous venez de créer ont bien été enregistrés (<a href="edit.php?post_type='+$('#post_type').val()+'">Voir les posts</a>)</p></div>').prependTo( parent ).delay(6000).fadeOut();
              },
              complete: function(jqXHR, textStatus) {
                  loading.css('visibility','hidden');
                  if( textStatus !== 'success' )
                      $('<div class="error" style="display: block !important"><p><strong>Unable to completed that action, please try again. '+textStatus+'</strong></p></div>').prependTo( parent );
              }
            });
        }
        e.preventDefault();
        return false;
      
      });
      
      //Paramètres d'url
      var hash = top.location.hash.replace('#', '');
      var pars = hash.split('&');
      var params = {};
      for(var i = 0; i < pars.length; i++){
        var propval = pars[i].split('=');
        params[propval[0]] = propval[1];
      }

      if(typeof params['post_type'] != undefined){
        jQuery('#nsi_fast_post_form #post_type').val(params['post_type'])
      }

      jQuery('<a href="tools.php?page=nsi_fast_post#post_type=<?= get_current_screen()->post_type ?>" class="page-title-action">Ajouter par lot</a>').insertAfter(jQuery(".wp-admin.edit-php .page-title-action").last());
    });
    </script>
    <?php
  }
}
$nsifp = new NsiFastPost();