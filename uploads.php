<?php
/*
 * Spelfabet Cody uploads page
 */
add_action( 'admin_menu', 'spelfabet_cody_menu');

// https://developer.wordpress.org/reference/functions/_wp_handle_upload/

function spelfabet_cody_menu(){
    add_submenu_page( 'spelfabet_cody', "Spelfabet Cody Uploads", 'Uploads', 'publish_posts', 'spelfabet_cody_uploads', 'spelfabet_cody_uploads', 55  );
}

function spelfabet_cody_uploads(){

    ?>
    <div class="spelfabet_cody_uploads">
        <h1>Spelfabet Cody Uploads page</h1>
        <form name="upload_CSV" enctype="multipart/form-data" method="post" action="/wp-admin/admin-post.php">
            <input name="action" type="hidden" value="cody_upload"/>
            <label for="upload_CSV">Upload Data File</label>
            <input id="upload_CSV" type="file" name="upload_CSV" />
            <br/>
            Only the first two columns of the spreadsheet are used.<br/>
            The first row is ignored (you can put column headings there, but we don't look at them).
            <br/>
            <ul class="vertical">
                <li><input type="radio" name="csv_type" value="word_pgc">Word PGC</li>
                <li><input type="radio" name="csv_type" value="word_structure">Word Structure</li>
                <li><label for="schema">Schema title</label> <input name="schema"/> <strong>Important</strong>: be consistent with this field as you enter all the CSV files below.</li>
                <li><input type="radio" name="csv_type" value="schema_pgc">Schema PGC</li>
                <li><input type="radio" name="csv_type" value="schema_structure">Schema Structure</li>
                <li><input type="radio" name="csv_type" value="schema_hfw">Schema HFW</li>
            </ul>
            <?php submit_button( "upload CSV file")?>
        </form>
        <table class="bordered">
            <tr>
                <th>Data type</th>
                <th>Columns</th>
            </tr>
            <tr>
                <td>Word PGC</td>
                <td>word, PGC as "t:tap"</td>
            </tr>
            <tr>
                <td>Word Structure</td>
                <td>word, structure as "VCC"</td>
            </tr>
            <tr>
                <td style="border:0;" colspan="2">Schema files:</td>
            </tr>
            <tr>
                <td>Schema PGC</td>
                <td>level as integer, PGC as "t:tap" </td>
            </tr>
            <tr>
                <td>Schema level</td>
                <td>level as integer, structure as "VCC"</td>
            </tr>
            <tr>
                <td>Schema High Frequency Words</td>
                <td>level as integer, HFW</td>
            </tr>
        </table>
    </div>
    <?php
}
/*
 *  file upload stuff
 */
add_filter( 'mime_types', 'wpse_mime_types' );
function wpse_mime_types( $existing_mimes ) {
    // Add csv to the list of allowed mime types
    $existing_mimes['csv'] = 'text/csv';
    return $existing_mimes;
}
require_once( ABSPATH . 'wp-admin/includes/file.php');
require_once( ABSPATH . 'wp-includes/capabilities.php');
require_once( ABSPATH . 'wp-includes/pluggable.php');
require_once( ABSPATH . 'wp-admin/includes/taxonomy.php');
require_once plugin_dir_path( __FILE__ ) . 'cody-includes.php';
add_action( 'admin_post_cody_upload', 'handle_upload' );
function handle_upload()
{
    $error = new WP_Error();
    if( empty($_FILES['upload_CSV']['name']) ) $error->add('nofile', 'No file uploaded');
    if( ! current_user_can('publish_posts') ) $error->add( 'privilege', 'Role publish_posts is required to upload a Cody spreadsheet');
    if( ! $error->has_errors() ) {
        $upload = wp_handle_upload($_FILES['upload_CSV'], ['test_form' => false]);
        if (isset($upload['error'])) {
            $error->add('bad upload', 'Error uploading file');
        }
    }
    if( ! $error->has_errors() ) {
        $post_type = $_POST['csv_type'];
        /*
         * schema if relevant
         */
        $schema = $_POST['schema'];
        if ($schema) {
            $schema_slug = 'schema_' . $schema;
            $term = get_term_by('slug', $schema_slug, 'schema');
            if (!$term) {
                $term = wp_insert_term($schema_slug, 'schema');
            }
        }
        if (!$schema && 0 === stripos($post_type, 'schema')) {
            $error->add('noschema', "Must specify schema for this type of upload");
        }
    }
    if( $error->has_errors() ){ ?>
        <h2>Errors:</h2>
        <ul class="vertical">
            <li>
                <?=implode('</li><li>', $error->get_error_messages())?>
            </li>
        </ul>
        <P>
            <a href="/wp-admin/admin.php?page=spelfabet_cody_uploads">Click here to try again</a>
        </P>
        <?php
        return;
    }
    /*
     * read the file
     */
    $handle = fopen($upload['file'], 'r');
    $headings = fgetcsv($handle); // discarded
    $lines_read = 0;
    while ($fields = fgetcsv($handle)) {
        $lines_read++;
        if( Count($fields) < 2) continue;
        switch ($post_type) {
            case "word_pgc":
                $word = $fields[0];
                $pgc = $fields[1];
                $posts = get_posts(['numberposts' => 10, 'post_type' => $post_type, 'exact' => true, 'title' => $word]);
                $post_filtered = array_filter($posts, function ($post) use ($pgc) {
                    return strtolower($post->post_excerpt) === strtolower($pgc);
                });
                if (count($post_filtered) === 1) { // exists, so ignore
                    continue;
                }
                $post = ['post_title' => $word, 'post_excerpt' => $pgc, 'post_type' => $post_type, 'post_status' => 'publish'];
                wp_insert_post($post, false, false);
                break;
            case "word_structure":
                $word = $fields[0];
                $structure = $fields[1];
                $posts = get_posts(['numberposts' => 1, 'post_type' => $post_type, 'exact' => true, 'title' => $word]);
                if (count($posts) === 1) { // existing post, update
                    $post = $posts[0];
                    $post->post_excerpt = $structure;
                    wp_update_post($post);
                } else { // new post, create
                    $post = ['post_title' => $word, 'post_excerpt' => $structure, 'post_type' => $post_type, 'post_status' => 'publish'];
                    wp_insert_post($post, false, false);
                }
                break;
            case "schema_pgc":
                $level = $fields[0];
                $pgc = $fields[1];
                $posts = get_posts(['numberposts' => 100, 'post_type' => $post_type, 'exact' => true, 'title' => $level]);
                $post_filtered = array_filter($posts, function ($post) use ($pgc, $schema) {
                    return strtolower($post->post_excerpt) === strtolower($pgc) && in_array($schema, $post->post_category);
                });
                if (count($post_filtered) === 1) continue;
                $post = ['post_title' => $level, 'post_excerpt' => $pgc, 'post_type' => $post_type, 'post_status' => 'publish', 'tax_input' => ['schema' => $schema]];
                wp_insert_post($post, false, false);
                break;
            case "schema_structure":
                $level = $fields[0];
                $structure = $fields[1];
                $posts = get_posts(['numberposts'=>100, 'post_type' => $post_type, 'exact' => true, 'title' => $level]);
                $post_filtered = array_filter($posts, function($post) use($structure, $schema ){
                    return strtolower($post->post_excerpt) === strtolower($structure) && in_array($schema, $post->post_category);
                });
                if( count($post_filtered) === 1 ) continue;
                $post = ['post_title'=>$level, 'post_excerpt' => $structure, 'post_type' => $post_type, 'post_status' => 'publish', 'tax_input' => ['schema' => $schema]];
                wp_insert_post( $post, false, false);
                break;
            case "schema_hfw":
                $level = $fields[0];
                $word = $fields[1];
                $posts = get_posts(['numberposts'=>100, 'post_type' => $post_type, 'exact' => true, 'title' => $level]);
                $post_filtered = array_filter($posts, function($post) use($structure, $schema ){
                    return strtolower($post->post_excerpt) === strtolower($structure) && in_array($schema, $post->post_category);
                });
                if( count($post_filtered) === 1 ) continue;
                $post = ['post_title'=>$level, 'post_excerpt' => $word, 'post_type' => $post_type, 'post_status' => 'publish', 'tax_input' => ['schema' => $schema]];
                wp_insert_post( $post, false, false);
                break;
        }
    }
    ?>
    <h2>Successfully uploaded <?=$lines_read?> rows</h2>
    <a href="/wp-admin/edit.php?post_type=<?=$post_type . ($schema ? "&schema=" . str_replace(" ", "-", $schema) : "")?>&filter_action=Filter&paged=1">View your upload</a>
    <?php
}