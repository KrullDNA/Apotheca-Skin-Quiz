<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pushes consented leads through the existing connector interface.
 *
 * It does not build connectors. It reuses the exact instances the Campaign
 * Monitor and Klaviyo add-ons register on the decoder's `ild_email_connectors`
 * filter, and calls their push() method. Each fired finding is sent as a custom
 * field so the lead lands already segmented.
 *
 * The push is queued on a scheduled event so it never blocks the visitor, and
 * only ever runs for a lead that consented.
 */
class ASQ_Connectors {

    const PUSH_HOOK = 'asq_push_lead';

    public function __construct() {
        add_action( 'asq_lead_recorded', array( $this, 'on_recorded' ), 10, 3 );
        add_action( self::PUSH_HOOK, array( $this, 'do_push' ), 10, 1 );
    }

    /**
     * Queue a push when a consented submission is recorded and a connector is
     * available to push to.
     */
    public function on_recorded( $lead_id, $submission_id, $data ) {
        if ( empty( $data['consent'] ) ) {
            return;
        }
        if ( ! self::has_connectors() ) {
            return;
        }
        if ( ! wp_next_scheduled( self::PUSH_HOOK, array( (int) $submission_id ) ) ) {
            wp_schedule_single_event( time() + 1, self::PUSH_HOOK, array( (int) $submission_id ) );
        }
    }

    /**
     * The configured connector instances the decoder's add-ons register.
     *
     * @return ILD_Email_Connector[]
     */
    public static function connectors() {
        if ( ! interface_exists( 'ILD_Email_Connector' ) ) {
            return array();
        }
        $registered = apply_filters( 'ild_email_connectors', array() );
        $out        = array();
        foreach ( (array) $registered as $connector ) {
            if ( $connector instanceof ILD_Email_Connector && $connector->is_configured() ) {
                $out[] = $connector;
            }
        }
        return $out;
    }

    public static function has_connectors() {
        if ( ! interface_exists( 'ILD_Email_Connector' ) ) {
            return false;
        }
        return ! empty( apply_filters( 'ild_email_connectors', array() ) );
    }

    /**
     * Push one submission's lead to every configured connector.
     */
    public function do_push( $submission_id ) {
        $sub = ASQ_Leads::get_submission( $submission_id );
        if ( ! $sub || empty( $sub['consent'] ) ) {
            return;
        }
        $connectors = self::connectors();
        if ( empty( $connectors ) ) {
            return;
        }

        $payload = self::payload( $sub );
        foreach ( $connectors as $connector ) {
            // Fire and forget: each connector records its own outcome. We never
            // build or manage the connectors, only hand them the lead.
            $connector->push( $payload );
        }
    }

    /**
     * The normalised payload the connectors receive. Matches the decoder's
     * documented shape (name, email, source, consent_date, tool) and adds the
     * fired findings as fields.
     */
    public static function payload( $sub ) {
        $payload = array(
            'name'         => '',
            'email'        => isset( $sub['email'] ) ? $sub['email'] : '',
            'source'       => isset( $sub['source'] ) ? $sub['source'] : '',
            'consent_date' => isset( $sub['created_at'] ) ? $sub['created_at'] : '',
            'tool'         => (string) apply_filters( 'asq_connector_tool_name', 'Apotheca Skin Quiz' ),
            'fields'       => self::fields( isset( $sub['findings'] ) ? $sub['findings'] : array() ),
        );
        return apply_filters( 'asq_connector_payload', $payload, $sub );
    }

    /**
     * Each fired finding as a custom field: id => label (F5 => Barrier
     * disruption). Filterable so field keys can be mapped to a provider's own.
     */
    public static function fields( $findings ) {
        $fields = array();
        foreach ( (array) $findings as $f ) {
            $id = isset( $f['id'] ) ? $f['id'] : '';
            if ( '' === $id ) {
                continue;
            }
            $fields[ $id ] = isset( $f['label'] ) ? $f['label'] : $id;
        }
        return apply_filters( 'asq_connector_fields', $fields, $findings );
    }
}

new ASQ_Connectors();
