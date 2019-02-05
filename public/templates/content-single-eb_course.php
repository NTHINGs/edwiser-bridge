<?php
/**
 * The template for displaying single course content.
 *
 * This template can be overridden by copying it to yourtheme/edwiser-bridge/
 *
 * @author      WisdmLabs
 *
 * @version     1.2.0
 */
namespace app\wisdmlabs\edwiserBridge;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//Variables
global $post;
global $wp;
$post_id = $post->ID;

// get currency
$payment_options = get_option('eb_paypal');
$currency = isset($payment_options['eb_paypal_currency']) ? $payment_options['eb_paypal_currency'] : 'USD';

$course_price_type = 'free';
$course_price = 0;
$short_description = '';

$course_options = get_post_meta($post_id, 'eb_course_options', true);
if (is_array($course_options)) {
    $course_price_type = (isset($course_options['course_price_type'])) ? $course_options['course_price_type'] : 'free';
    $course_price = (isset($course_options['course_price']) &&
            is_numeric($course_options['course_price'])) ?
            $course_options['course_price'] : 0;
    $course_closed_url = (isset($course_options['course_closed_url'])) ?
            $course_options['course_closed_url'] : '#';
    $short_description = (isset($course_options['course_short_description'])) ?
            $course_options['course_short_description'] : '';
}

if (is_numeric($course_price)) {
    if ($currency == 'USD') {
        $course_price_formatted = '$'.$course_price;
    } else {
        $course_price_formatted = $currency.' '.$course_price;
    }

    if ($course_price == 0) {
        $course_price_formatted = __('Free', 'eb-textdomain');
    }
}

$course_class = null;
$user_id = get_current_user_id();
$logged_in = !empty($user_id);
$has_access = edwiserBridgeInstance()->enrollmentManager()->userHasCourseAccess($user_id, $post->ID);

$course_id = $post_id;

$categories = array();
$terms = wp_get_post_terms(
    $course_id,
    'eb_course_cat',
    array(
    'orderby' => 'name',
    'order' => 'ASC',
    'fields' => 'all', )
);

if (is_array($terms)) {
    foreach ($terms as $term) {
        $lnk = get_term_link($term->term_id, 'eb_course_cat');
        $categories[] = '<a href="'.esc_url($lnk).'" target="_blank">'.$term->name.'</a>';
    }
}

/*
 * Check is course has expiry date
 */
if (isset($course_options['course_expirey']) && $course_options['course_expirey'] == 'yes') {
    if (is_user_logged_in() && $has_access) {
        $expiryDateTime = '<span><strong>'.EBEnrollmentManager::accessRemianing($user_id, $post->ID).' '.__(' days access remaining', 'eb-textdomain').'</strong></span>';
    } else {
        $expiryDateTime = '<span><strong>'.__('Includes  ', 'eb-textdomain').' '.$course_options['num_days_course_access'].__(' days access', 'eb-textdomain').'</strong> </span>';
    }
} else {
    $expiryDateTime = '<span><strong>'.__('Includes lifetime access ', 'eb-textdomain').'</strong></span>';
}
?>

<article id="course-<?php the_ID(); ?>" class="type-post hentry single-course" >
    <h1 class="entry-title">
        <?php
        if (is_single()) {
            the_title();
        } else {
            ?>
            <a href="<?php the_permalink();
            ?>" rel="bookmark"><?php the_title();
            ?></a>
            <?php
        }
        ?>          
    </h1>
    <div>
        <div class="eb-course-img-wrapper">
            <?php
            if (has_post_thumbnail()) {
                the_post_thumbnail('course_single');
            } else {
                echo '<img src="'.EB_PLUGIN_URL.'images/no-image.jpg" />';
            }
            ?>
        </div>

        <div class="eb-course-summary">
            <?php
            if (!is_search()) {
                if (!$has_access || !is_user_logged_in()) {
                    if ($course_price_type == 'paid' || $course_price_type == 'free') {
                        ob_start();
                        ?>
                        <div class="<?php echo 'wdm-price'.$course_price_type;
                        ?>">
                                    <?php echo '<h3>'.$course_price_formatted.'</h3>';
                        ?>
                        </div>
                        <?php
                        echo ob_get_clean();
                    }
                    if ($course_price_type == 'free') {
                        echo EBPaymentManager::takeCourseButton($post->ID);
                    } else if ($course_price_type == 'paid') {
                        echo pagar_modal($post->ID, $course_price_formatted);
                    }
                } else {
                    echo EBPaymentManager::accessCourseButton($post->ID);
                }

                if (count($categories)) {
                    ?>
                    <div class="eb-cat-wrapper">
                        <span><strong><?php _e('Categories: ', 'eb-textdomain');
                    ?></strong><?php echo implode(', ', $categories);
                    ?></span>
                    </div>                  
                    <?php
                }
                ?>
                <div class="eb-validity-wrapper">
                    <?php echo $expiryDateTime;
                ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="eb-course-desc-wrapper">
        <?php
        if (is_search()) {
            ?>
            <div class="entry-summary"><?php the_excerpt();
            ?></div>
                <?php
        } else {
            ?>
            <h2><?php _e('Course Overview', 'eb-textdomain') ?></h2>
            <?php
            the_content();

            if (!$has_access || !is_user_logged_in()) {
                echo pagar_modal($post->ID, $course_price_formatted);
            } else {
                echo EBPaymentManager::accessCourseButton($post->ID);
            }
        }
        ?>
    </div>
</article><!-- #post -->

<?php
function pagar_modal($postID, $course_price_formatted) {
    ob_start();
    ?>
        <button type="button" class="btn btn-lg btn-default" data-toggle="modal" data-target="#comprar">
            Comprar Curso
        </button>
        <div class="modal fade" id="comprar" tabindex="-1" role="dialog" aria-labelledby="comprarLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="comprarLabel">Comprar Curso</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h4>Pagar con Tarjeta</h4>
                        <?php
                            echo EBPaymentManager::takeCourseButton($postID);
                        ?>
                        <h4 class="mt-5">Pagar con Depósito Bancario</h4>
                        <p>Pagar la cantidad de: <?php echo $course_price_formatted;?></p>
                        <p>Número de Cuenta: 56618035489</p>
                        <p>Banco: Santander</p>
                        <p>A nombre de: Cuitlahuac Hernán Valenzuela Corral</p>
                        <p>Mandar foto del ticket del deposito al siguiente correo: <a href="mailto:cui5@hotmail.com">cui5@hotmail.com</a></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php
    echo ob_get_clean();
}
?>