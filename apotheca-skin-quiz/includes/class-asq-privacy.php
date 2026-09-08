<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WordPress personal-data export and erase for Apotheca Skin Quiz.
 *
 * Registers the plugin with WordPress' built-in privacy tools (Tools →
 * Export/Erase Personal Data), so a person's quiz data can be handed over or
 * removed on request. Erasing goes through ASQ_Leads::delete_by_email(), which
 * removes the lead and every joined response together, leaving no orphan rows.
 */
class ASQ_Privacy {

    public function __construct() {
        add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
    }

    public function register_exporter( $exporters ) {
        $exporters['apotheca-skin-quiz'] = array(
            'exporter_friendly_name' => __( 'Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'callback'               => array( $this, 'export' ),
        );
        return $exporters;
    }

    public function register_eraser( $erasers ) {
        $erasers['apotheca-skin-quiz'] = array(
            'eraser_friendly_name' => __( 'Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'callback'             => array( $this, 'erase' ),
        );
        return $erasers;
    }

    /* ────────── Export ────────── */

    /**
     * Everything held for one email: the lead record plus each quiz response
     * (date, consent, findings, full answer set). Small per person, so it all
     * comes back in one page.
     */
    public function export( $email_address, $page = 1 ) {
        $data     = array();
        $lead_ids = ASQ_Leads::get_lead_ids_by_email( $email_address );

        foreach ( $lead_ids as $lead_id ) {
            $lead = ASQ_Leads::get_lead( $lead_id );
            if ( $lead ) {
                $data[] = array(
                    'group_id'    => 'asq_lead',
                    'group_label' => __( 'Apotheca Skin Quiz contact', 'apotheca-skin-quiz' ),
                    'item_id'     => 'asq-lead-' . $lead_id,
                    'data'        => array(
                        array( 'name' => __( 'Email', 'apotheca-skin-quiz' ), 'value' => $lead['email'] ),
                        array( 'name' => __( 'Consent given', 'apotheca-skin-quiz' ), 'value' => $lead['consent'] ? __( 'Yes', 'apotheca-skin-quiz' ) : __( 'No', 'apotheca-skin-quiz' ) ),
                        array( 'name' => __( 'Consent wording shown', 'apotheca-skin-quiz' ), 'value' => (string) $lead['consent_text'] ),
                        array( 'name' => __( 'Source page', 'apotheca-skin-quiz' ), 'value' => (string) $lead['source'] ),
                        array( 'name' => __( 'First seen', 'apotheca-skin-quiz' ), 'value' => (string) $lead['created_at'] ),
                        array( 'name' => __( 'Last seen', 'apotheca-skin-quiz' ), 'value' => (string) $lead['updated_at'] ),
                        array( 'name' => __( 'Unsubscribed', 'apotheca-skin-quiz' ), 'value' => $lead['unsubscribed'] ? (string) $lead['unsubscribed'] : __( 'No', 'apotheca-skin-quiz' ) ),
                    ),
                );
            }

            foreach ( ASQ_Leads::get_submissions_for_lead( $lead_id ) as $sub ) {
                $data[] = array(
                    'group_id'    => 'asq_responses',
                    'group_label' => __( 'Apotheca Skin Quiz responses', 'apotheca-skin-quiz' ),
                    'item_id'     => 'asq-response-' . $sub['id'],
                    'data'        => array(
                        array( 'name' => __( 'Date', 'apotheca-skin-quiz' ), 'value' => (string) $sub['created_at'] ),
                        array( 'name' => __( 'Quiz', 'apotheca-skin-quiz' ), 'value' => get_the_title( (int) $sub['finder_id'] ) ?: ( '#' . $sub['finder_id'] ) ),
                        array( 'name' => __( 'Findings', 'apotheca-skin-quiz' ), 'value' => ASQ_Leads::findings_to_text( $sub['findings'] ) ),
                        array( 'name' => __( 'Answers', 'apotheca-skin-quiz' ), 'value' => ASQ_Leads::answers_to_text( $sub['answers'] ) ),
                    ),
                );
            }
        }

        return array(
            'data' => $data,
            'done' => true,
        );
    }

    /* ────────── Erase ────────── */

    /**
     * Remove the lead and all their responses in one operation.
     */
    public function erase( $email_address, $page = 1 ) {
        $removed = ASQ_Leads::delete_by_email( $email_address );

        $messages = array();
        if ( $removed > 0 ) {
            $messages[] = sprintf(
                /* translators: %s: number of rows removed */
                _n( '%s Apotheca Skin Quiz record removed.', '%s Apotheca Skin Quiz records removed.', $removed, 'apotheca-skin-quiz' ),
                number_format_i18n( $removed )
            );
        }

        return array(
            'items_removed'  => $removed > 0,
            'items_retained' => false,
            'messages'       => $messages,
            'done'           => true,
        );
    }
}

new ASQ_Privacy();
