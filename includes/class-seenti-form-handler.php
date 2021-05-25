<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       vexel.mx
 * @since      1.0.0
 *
 * @package    Seenti_Form_Handler
 * @subpackage Seenti_Form_Handler/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Seenti_Form_Handler
 * @subpackage Seenti_Form_Handler/includes
 * @author     David Leal <contacto@vexel.mx>
 */
class Seenti_Form_Handler
{
    /**
     * Seenti_Form_Handler constructor.
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_route_handler'));
        add_action('init', array($this, 'create_custom_post_type'));
    }

    /**
     * Create custom post type
     */
    public function create_custom_post_type()
    {
        register_post_type('expo_form_entry',
            // CPT Options
            array(
                'labels' => array(
                    'name' => __('Registro de Exposicion'),
                    'singular_name' => __('Registros de Exposiciones')
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'registros-exposiciones'),
                'show_in_rest' => false,
                'show_in_menu' => true,
                'show_ui' => true,
            )
        );
    }

    /**
     * Register the route handler
     */
    public function register_route_handler()
    {
        register_rest_route('seenti-handler/v1', '/process', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_form_request')
        ));
    }

    /**
     * Process the form request
     *
     * @param WP_REST_Request $request
     * @return false
     */
    public function process_form_request(\WP_REST_Request $request)
    {
        $data = $request->get_body_params();

        if (empty($data)) {
            return false;
        }

        //The url you wish to send the POST request to
        $url = "https://webto.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8";

        //The data you want to send via POST
        $fields = [
            'oid' => "00D3i000000DxCc",
            'retURL' => "http://",
            'debug' => "1",
            'debugEmail' => 'fabino2210g@gmail.com',
            'first_name' => isset($data['first_name']) ? urlencode($data['first_name']) : '',   //nombre
            'last_name' => isset($data['last_name']) ? urlencode($data['last_name']) : '',  //Apellido Paterno
            '00N3i00000CFL6S' => isset($data["00N3i00000CFL6S"]) ? urlencode($data["00N3i00000CFL6S"]) : '',  //meterno FIX
            'email' => isset($data['email']) ? urlencode($data['email']) : '',   //E-MAIL
            'company' => isset($data['company']) ? urlencode($data['company']) : '',   //Empresa
            '00N3i00000CFL6a' => isset($data['00N3i00000CFL6a']) ? urlencode($data['00N3i00000CFL6a']) : '',    //Ciudad
            'phone' => isset($data['phone']) ? urlencode($data['phone']) : '',  //Teléfono
            '00N3i00000CFL6Z' => isset($data['00N3i00000CFL6Z']) ? urlencode($data['00N3i00000CFL6Z']) : '', // cargo
            '00N3i00000CFL6U' => isset($data['00N3i00000CFL6U']) ? urlencode($data['00N3i00000CFL6U']) : '', // area
            'industry' => isset($data['industry']) ? urlencode($data['industry']) : '',   //Industria
            '00N3i00000CFL8S' => isset($data['00N3i00000CFL8S']) ? urlencode($data['00N3i00000CFL8S']) : '', // Pais
            '00N3i00000CFL6g' => isset($data['00N3i00000CFL6g']) ? urlencode($data['00N3i00000CFL6g']) : '', //Como se entero
            '00N3i00000CFL7g' => isset($data['00N3i00000CFL7g']) ? urlencode($data['00N3i00000CFL7g']) : '',  //Mensaje
            //'industry'        => isset( $data['Industria'] ) ? urlencode( $data['Industria'] ) : '', //industria
            'lead_source' => 'Web',
            '00N3i00000CFL74' => isset($data['Tipo de cuenta']) ? urlencode($data['Tipo de cuenta']) : '', //Tipo de cuenta
            '00N3i00000CFL97' => "005",  //005-RENEX
        ];

        // Store the custom post type.
        wp_insert_post(array(
            'post_title' => sprintf('Registro en formulario de Expositores - %s %s', $fields['first_name'], $fields['last_name']),
            'post_type' => 'expo_form_entry',
            'post_content' => implode("<br>", $fields),
        ));

        // Send the request to salesforce.
        $this->send_curl_request($url, $fields);
    }

    public function send_curl_request($url, $fields)
    {
        $fields_string = '';

        //url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        //execute post
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);
    }

}
