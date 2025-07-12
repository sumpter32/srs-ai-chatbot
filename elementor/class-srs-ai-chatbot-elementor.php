<?php
/**
 * Elementor Integration for SRS AI ChatBot
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Elementor {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_elementor_category'));
    }

    /**
     * Add Elementor category
     */
    public function add_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'srs-ai-chatbot',
            array(
                'title' => __('SRS AI ChatBot', 'srs-ai-chatbot'),
                'icon' => 'fa fa-plug',
            )
        );
    }

    /**
     * Register widgets
     */
    public function register_widgets() {
        require_once SRS_AI_CHATBOT_PLUGIN_PATH . 'elementor/widgets/chatbot-widget.php';
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \SRS_AI_ChatBot_Elementor_Widget());
    }
}

/**
 * Elementor ChatBot Widget
 */
class SRS_AI_ChatBot_Elementor_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'srs-ai-chatbot';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('AI ChatBot', 'srs-ai-chatbot');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-comments';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return array('srs-ai-chatbot');
    }

    /**
     * Register widget controls
     */
    protected function _register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('ChatBot Settings', 'srs-ai-chatbot'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        // Get available chatbots
        $database = new SRS_AI_ChatBot_Database();
        $chatbots = $database->get_active_chatbots();
        $chatbot_options = array();
        
        foreach ($chatbots as $chatbot) {
            $chatbot_options[$chatbot->id] = $chatbot->name;
        }

        $this->add_control(
            'chatbot_id',
            array(
                'label' => __('Select ChatBot', 'srs-ai-chatbot'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => !empty($chatbots) ? $chatbots[0]->id : '',
                'options' => $chatbot_options,
            )
        );

        $this->add_control(
            'widget_height',
            array(
                'label' => __('Height', 'srs-ai-chatbot'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'vh'),
                'range' => array(
                    'px' => array(
                        'min' => 300,
                        'max' => 800,
                        'step' => 10,
                    ),
                    'vh' => array(
                        'min' => 30,
                        'max' => 90,
                        'step' => 5,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 500,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .srs-chatbot-elementor-container' => 'height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Style', 'srs-ai-chatbot'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'border_radius',
            array(
                'label' => __('Border Radius', 'srs-ai-chatbot'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .srs-chatbot-window' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'box_shadow',
                'label' => __('Box Shadow', 'srs-ai-chatbot'),
                'selector' => '{{WRAPPER}} .srs-chatbot-window',
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $chatbot_id = $settings['chatbot_id'];

        if (!$chatbot_id) {
            echo '<p>' . __('Please select a chatbot in the widget settings.', 'srs-ai-chatbot') . '</p>';
            return;
        }

        // Get chatbot
        $database = new SRS_AI_ChatBot_Database();
        $chatbot = $database->get_chatbot($chatbot_id);

        if (!$chatbot) {
            echo '<p>' . __('Selected chatbot not found.', 'srs-ai-chatbot') . '</p>';
            return;
        }

        echo '<div class="srs-chatbot-elementor-container">';
        
        // Render chatbot interface
        $public = new SRS_AI_ChatBot_Public();
        $public->render_chat_interface($chatbot, true);
        
        echo '</div>';
    }

    /**
     * Render widget content template
     */
    protected function _content_template() {
        ?>
        <div class="srs-chatbot-elementor-container">
            <div class="srs-chatbot-placeholder">
                <i class="eicon-comments"></i>
                <p><?php _e('AI ChatBot will be displayed here', 'srs-ai-chatbot'); ?></p>
            </div>
        </div>
        <?php
    }
}
