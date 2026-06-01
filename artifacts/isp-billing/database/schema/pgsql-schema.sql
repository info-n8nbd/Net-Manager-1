--
-- PostgreSQL database dump
--

\restrict TdrKIDY4my5RRp8zpuFhhB0B7WGvdijyG75MHqZzeiBbXl8aRkfzW1rcdWzYj76

-- Dumped from database version 16.10
-- Dumped by pg_dump version 16.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.accounts (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    account_number character varying(255),
    type character varying(255) NOT NULL,
    opening_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    current_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT accounts_type_check CHECK (((type)::text = ANY ((ARRAY['cash'::character varying, 'bank'::character varying, 'mobile_banking'::character varying])::text[])))
);


--
-- Name: accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.accounts_id_seq OWNED BY public.accounts.id;


--
-- Name: action_confirmations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.action_confirmations (
    id bigint NOT NULL,
    token character varying(255) NOT NULL,
    user_id bigint NOT NULL,
    action_type character varying(255) NOT NULL,
    action_description character varying(255) NOT NULL,
    action_data json NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    confirmed_at timestamp(0) without time zone,
    ip_address character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT action_confirmations_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'confirmed'::character varying, 'expired'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: action_confirmations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.action_confirmations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: action_confirmations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.action_confirmations_id_seq OWNED BY public.action_confirmations.id;


--
-- Name: activity_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_logs (
    id bigint NOT NULL,
    user_id bigint,
    action character varying(255) NOT NULL,
    module character varying(255) NOT NULL,
    description text,
    ip_address character varying(45),
    user_agent text,
    old_values json,
    new_values json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_logs_id_seq OWNED BY public.activity_logs.id;


--
-- Name: admin_overrides; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin_overrides (
    id bigint NOT NULL,
    admin_id bigint NOT NULL,
    technician_action_log_id bigint NOT NULL,
    client_id bigint,
    override_type character varying(50) NOT NULL,
    action_category character varying(50) NOT NULL,
    before_values json NOT NULL,
    after_values json NOT NULL,
    correction_data json,
    reason text NOT NULL,
    status character varying(20) DEFAULT 'completed'::character varying NOT NULL,
    balance_adjustment numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    network_sync_required boolean DEFAULT false NOT NULL,
    network_sync_completed boolean DEFAULT false NOT NULL,
    network_synced_at timestamp(0) without time zone,
    ip_address character varying(45),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: admin_overrides_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.admin_overrides_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: admin_overrides_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.admin_overrides_id_seq OWNED BY public.admin_overrides.id;


--
-- Name: advance_payment_rewards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.advance_payment_rewards (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    points integer DEFAULT 0 NOT NULL,
    reward_type character varying(255) NOT NULL,
    description text,
    is_redeemed boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: advance_payment_rewards_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.advance_payment_rewards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: advance_payment_rewards_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.advance_payment_rewards_id_seq OWNED BY public.advance_payment_rewards.id;


--
-- Name: advance_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.advance_payments (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    payment_method_id bigint,
    payment_date date NOT NULL,
    used_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    remaining_amount numeric(12,2) NOT NULL,
    remarks text,
    collected_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: advance_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.advance_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: advance_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.advance_payments_id_seq OWNED BY public.advance_payments.id;


--
-- Name: advance_salaries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.advance_salaries (
    id bigint NOT NULL,
    employee_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    advance_date date NOT NULL,
    deducted_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    remaining_amount numeric(12,2) NOT NULL,
    deduction_months integer DEFAULT 1 NOT NULL,
    reason text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT advance_salaries_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying, 'completed'::character varying])::text[])))
);


--
-- Name: advance_salaries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.advance_salaries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: advance_salaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.advance_salaries_id_seq OWNED BY public.advance_salaries.id;


--
-- Name: agent_commissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_commissions (
    id bigint NOT NULL,
    agent_id bigint NOT NULL,
    client_id bigint,
    client_name character varying(255),
    client_pppoe_id character varying(255),
    zone_address character varying(255),
    purpose character varying(255),
    note text,
    date date,
    payment_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    commission_percent numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    commission_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    month integer,
    year integer,
    company_id bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    type character varying(20) DEFAULT 'commission'::character varying NOT NULL,
    payment_id bigint
);


--
-- Name: agent_commissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.agent_commissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: agent_commissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.agent_commissions_id_seq OWNED BY public.agent_commissions.id;


--
-- Name: agents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agents (
    id bigint NOT NULL,
    agent_id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    default_commission numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    father_name character varying(255),
    mother_name character varying(255),
    date_of_birth date,
    joining_date date,
    gender character varying(255) DEFAULT 'male'::character varying NOT NULL,
    marital_status character varying(255) DEFAULT 'unmarried'::character varying NOT NULL,
    blood_group character varying(255),
    national_id character varying(255),
    present_address text,
    permanent_address text,
    personal_contact character varying(255),
    whatsapp_no character varying(255),
    office_contact character varying(255),
    family_contact character varying(255),
    first_reference_no character varying(255),
    second_reference_no character varying(255),
    email character varying(255),
    image character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    company_id bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT agents_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying])::text[]))),
    CONSTRAINT agents_marital_status_check CHECK (((marital_status)::text = ANY ((ARRAY['unmarried'::character varying, 'married'::character varying])::text[]))),
    CONSTRAINT agents_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


--
-- Name: agents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.agents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: agents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.agents_id_seq OWNED BY public.agents.id;


--
-- Name: announcements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.announcements (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    target character varying(255) DEFAULT 'all'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    start_date timestamp(0) without time zone,
    end_date timestamp(0) without time zone,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: announcements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.announcements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: announcements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.announcements_id_seq OWNED BY public.announcements.id;


--
-- Name: api_refresh_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.api_refresh_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    token character varying(128) NOT NULL,
    device_name character varying(255),
    device_id character varying(255),
    ip_address character varying(255),
    expires_at timestamp(0) without time zone NOT NULL,
    last_used_at timestamp(0) without time zone,
    is_revoked boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: api_refresh_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.api_refresh_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: api_refresh_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.api_refresh_tokens_id_seq OWNED BY public.api_refresh_tokens.id;


--
-- Name: api_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.api_tokens (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities json,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL
);


--
-- Name: api_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.api_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: api_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.api_tokens_id_seq OWNED BY public.api_tokens.id;


--
-- Name: application_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.application_documents (
    id bigint NOT NULL,
    application_id bigint NOT NULL,
    document_type character varying(255) NOT NULL,
    document_name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    file_type character varying(255),
    file_size integer,
    uploaded_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: application_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.application_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: application_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.application_documents_id_seq OWNED BY public.application_documents.id;


--
-- Name: application_status_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.application_status_logs (
    id bigint NOT NULL,
    application_id bigint NOT NULL,
    from_status character varying(255),
    to_status character varying(255) NOT NULL,
    note text,
    changed_by bigint NOT NULL,
    changes json,
    ip_address character varying(255),
    user_agent character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: application_status_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.application_status_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: application_status_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.application_status_logs_id_seq OWNED BY public.application_status_logs.id;


--
-- Name: area_outage_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.area_outage_logs (
    id bigint NOT NULL,
    zone_id bigint NOT NULL,
    affected_clients integer NOT NULL,
    outage_type character varying(255) NOT NULL,
    description text,
    started_at timestamp(0) without time zone NOT NULL,
    resolved_at timestamp(0) without time zone,
    resolved_by bigint,
    resolution_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: area_outage_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.area_outage_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: area_outage_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.area_outage_logs_id_seq OWNED BY public.area_outage_logs.id;


--
-- Name: area_profitability; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.area_profitability (
    id bigint NOT NULL,
    zone_id bigint NOT NULL,
    month character varying(255) NOT NULL,
    total_clients integer DEFAULT 0 NOT NULL,
    total_revenue numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    collection_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    operating_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    profit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    profit_rank integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: area_profitability_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.area_profitability_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: area_profitability_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.area_profitability_id_seq OWNED BY public.area_profitability.id;


--
-- Name: areas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.areas (
    id bigint NOT NULL,
    zone_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint,
    reseller_id bigint
);


--
-- Name: areas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.areas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: areas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.areas_id_seq OWNED BY public.areas.id;


--
-- Name: attendances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.attendances (
    id bigint NOT NULL,
    employee_id bigint NOT NULL,
    attendance_date date NOT NULL,
    check_in time(0) without time zone,
    check_out time(0) without time zone,
    status character varying(255) DEFAULT 'present'::character varying NOT NULL,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT attendances_status_check CHECK (((status)::text = ANY ((ARRAY['present'::character varying, 'absent'::character varying, 'late'::character varying, 'half_day'::character varying, 'leave'::character varying])::text[])))
);


--
-- Name: attendances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.attendances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attendances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.attendances_id_seq OWNED BY public.attendances.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    action character varying(255) NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    changes json,
    ip_address character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: automation_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.automation_settings (
    id bigint NOT NULL,
    bill_generate_time time(0) without time zone DEFAULT '09:00:00'::time without time zone NOT NULL,
    disconnect_time time(0) without time zone DEFAULT '23:59:00'::time without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: automation_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.automation_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: automation_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.automation_settings_id_seq OWNED BY public.automation_settings.id;


--
-- Name: backups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.backups (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    file_path character varying(255),
    file_size bigint DEFAULT '0'::bigint NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    metadata json,
    created_by bigint,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: backups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.backups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: backups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.backups_id_seq OWNED BY public.backups.id;


--
-- Name: bandwidth_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bandwidth_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    mikrotik_device_id bigint,
    rx_bytes bigint DEFAULT '0'::bigint NOT NULL,
    tx_bytes bigint DEFAULT '0'::bigint NOT NULL,
    rx_rate integer DEFAULT 0 NOT NULL,
    tx_rate integer DEFAULT 0 NOT NULL,
    session_uptime character varying(255),
    log_date date NOT NULL,
    log_hour character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: bandwidth_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bandwidth_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bandwidth_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bandwidth_logs_id_seq OWNED BY public.bandwidth_logs.id;


--
-- Name: bandwidth_queues; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bandwidth_queues (
    id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    target character varying(255) NOT NULL,
    max_limit character varying(255) NOT NULL,
    burst_limit character varying(255),
    burst_threshold character varying(255),
    burst_time integer,
    priority integer DEFAULT 8 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: bandwidth_queues_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bandwidth_queues_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bandwidth_queues_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bandwidth_queues_id_seq OWNED BY public.bandwidth_queues.id;


--
-- Name: banks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.banks (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    branch character varying(255),
    account_name character varying(255) NOT NULL,
    account_number character varying(255) NOT NULL,
    opening_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    current_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: banks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.banks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: banks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.banks_id_seq OWNED BY public.banks.id;


--
-- Name: bill_adjustments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bill_adjustments (
    id bigint NOT NULL,
    bill_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    type character varying(255) NOT NULL,
    reason text NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT bill_adjustments_type_check CHECK (((type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying])::text[])))
);


--
-- Name: bill_adjustments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bill_adjustments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bill_adjustments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bill_adjustments_id_seq OWNED BY public.bill_adjustments.id;


--
-- Name: bill_corrections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bill_corrections (
    id bigint NOT NULL,
    correction_no character varying(50) NOT NULL,
    bill_id bigint NOT NULL,
    client_id bigint NOT NULL,
    correction_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    original_package_amount numeric(12,2) NOT NULL,
    original_discount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    original_total_amount numeric(12,2) NOT NULL,
    original_due_amount numeric(12,2) NOT NULL,
    original_status character varying(20) NOT NULL,
    new_package_amount numeric(12,2),
    new_discount numeric(12,2),
    new_total_amount numeric(12,2),
    adjustment_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    replacement_bill_id bigint,
    reason text NOT NULL,
    admin_notes text,
    requested_by bigint,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    ip_address inet,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT bill_corrections_correction_type_check CHECK (((correction_type)::text = ANY ((ARRAY['amount_correction'::character varying, 'cancellation'::character varying, 'replacement'::character varying])::text[]))),
    CONSTRAINT bill_corrections_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: bill_corrections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bill_corrections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bill_corrections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bill_corrections_id_seq OWNED BY public.bill_corrections.id;


--
-- Name: bill_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bill_items (
    id bigint NOT NULL,
    bill_id bigint NOT NULL,
    description character varying(255) NOT NULL,
    amount numeric(12,2) NOT NULL,
    type character varying(255) DEFAULT 'charge'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT bill_items_type_check CHECK (((type)::text = ANY ((ARRAY['charge'::character varying, 'discount'::character varying])::text[])))
);


--
-- Name: bill_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bill_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bill_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bill_items_id_seq OWNED BY public.bill_items.id;


--
-- Name: bill_splits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bill_splits (
    id bigint NOT NULL,
    bill_id bigint NOT NULL,
    client_id bigint NOT NULL,
    first_payment numeric(12,2) NOT NULL,
    second_payment numeric(12,2) NOT NULL,
    first_due_date date NOT NULL,
    second_due_date date NOT NULL,
    first_paid boolean DEFAULT false NOT NULL,
    second_paid boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: bill_splits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bill_splits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bill_splits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bill_splits_id_seq OWNED BY public.bill_splits.id;


--
-- Name: bills; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bills (
    id bigint NOT NULL,
    bill_no character varying(255) NOT NULL,
    client_id bigint NOT NULL,
    billing_month integer NOT NULL,
    billing_year integer NOT NULL,
    bill_date date NOT NULL,
    due_date date NOT NULL,
    package_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    previous_due numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    adjustment numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    discount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(12,2) NOT NULL,
    paid_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    due_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'unpaid'::character varying NOT NULL,
    remarks text,
    generated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    advance_used numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    auto_reconnect boolean DEFAULT false NOT NULL,
    discount_reason character varying(255),
    is_corrected boolean DEFAULT false NOT NULL,
    is_cancelled boolean DEFAULT false NOT NULL,
    is_replaced boolean DEFAULT false NOT NULL,
    replaced_by_bill_id bigint,
    replaces_bill_id bigint,
    late_fee_applied numeric(10,2),
    is_prorated boolean DEFAULT false NOT NULL,
    connection_date date,
    advance_applied_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT bills_status_check CHECK (((status)::text = ANY ((ARRAY['unpaid'::character varying, 'partial'::character varying, 'paid'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: bills_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bills_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bills_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bills_id_seq OWNED BY public.bills.id;


--
-- Name: boxes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.boxes (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    zone_id bigint,
    area_id bigint,
    location character varying(255),
    capacity integer DEFAULT 16 NOT NULL,
    description text,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT boxes_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[])))
);


--
-- Name: boxes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.boxes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: boxes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.boxes_id_seq OWNED BY public.boxes.id;


--
-- Name: branches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.branches (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(20) NOT NULL,
    address text,
    phone character varying(255),
    email character varying(255),
    contact_person character varying(255),
    manager_id bigint,
    is_active boolean DEFAULT true NOT NULL,
    is_main boolean DEFAULT false NOT NULL,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: branches_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.branches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: branches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.branches_id_seq OWNED BY public.branches.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: card_distributors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.card_distributors (
    id bigint NOT NULL,
    user_id bigint,
    name character varying(255) NOT NULL,
    phone character varying(255),
    email character varying(255),
    address character varying(255),
    commission_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    total_sales numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_commission numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: card_distributors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.card_distributors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: card_distributors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.card_distributors_id_seq OWNED BY public.card_distributors.id;


--
-- Name: card_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.card_transactions (
    id bigint NOT NULL,
    scratch_card_id bigint NOT NULL,
    client_id bigint,
    distributor_id bigint,
    transaction_type character varying(255) NOT NULL,
    amount numeric(10,2) NOT NULL,
    status character varying(255) DEFAULT 'success'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    commission numeric(10,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: card_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.card_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: card_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.card_transactions_id_seq OWNED BY public.card_transactions.id;


--
-- Name: churn_predictions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.churn_predictions (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    churn_probability numeric(5,2) NOT NULL,
    risk_factors json,
    recommended_action character varying(255),
    action_taken boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: churn_predictions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.churn_predictions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: churn_predictions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.churn_predictions_id_seq OWNED BY public.churn_predictions.id;


--
-- Name: client_activity_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_activity_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    action_type character varying(255) NOT NULL,
    old_value character varying(255),
    new_value character varying(255),
    reason text,
    triggered_by character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    performed_by bigint,
    ip_address character varying(255),
    extra_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: client_activity_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_activity_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_activity_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_activity_logs_id_seq OWNED BY public.client_activity_logs.id;


--
-- Name: client_additional_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_additional_payments (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    payment_type character varying(255) DEFAULT 'other'::character varying NOT NULL,
    description character varying(255),
    amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    payment_date date,
    payment_method_id bigint,
    collected_by bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    receipt_no character varying(255),
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    custom_name character varying(255),
    company_id bigint,
    money_receipt_no character varying(50),
    transaction_id character varying(100),
    sender_number character varying(20)
);


--
-- Name: client_additional_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_additional_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_additional_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_additional_payments_id_seq OWNED BY public.client_additional_payments.id;


--
-- Name: client_agreements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_agreements (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    agreement_content text,
    otp_code character varying(255),
    otp_verified boolean DEFAULT false NOT NULL,
    signature_image character varying(255),
    ip_address character varying(255),
    device_info character varying(255),
    signed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_agreements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_agreements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_agreements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_agreements_id_seq OWNED BY public.client_agreements.id;


--
-- Name: client_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_documents (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    document_type character varying(255) NOT NULL,
    document_name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: client_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_documents_id_seq OWNED BY public.client_documents.id;


--
-- Name: client_expiry_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_expiry_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    action character varying(255) NOT NULL,
    previous_status character varying(255),
    new_status character varying(255),
    notes text,
    expire_date timestamp(0) without time zone,
    mikrotik_success boolean,
    profile_shifted boolean,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_expiry_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_expiry_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_expiry_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_expiry_logs_id_seq OWNED BY public.client_expiry_logs.id;


--
-- Name: client_grace_periods; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_grace_periods (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    grace_days integer DEFAULT 5 NOT NULL,
    reason text,
    approved_by bigint NOT NULL,
    valid_until date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_grace_periods_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_grace_periods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_grace_periods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_grace_periods_id_seq OWNED BY public.client_grace_periods.id;


--
-- Name: client_lifetime_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_lifetime_values (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    total_revenue numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    avg_monthly_revenue numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    tenure_months integer DEFAULT 0 NOT NULL,
    predicted_clv numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    segment character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_lifetime_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_lifetime_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_lifetime_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_lifetime_values_id_seq OWNED BY public.client_lifetime_values.id;


--
-- Name: client_line_shifts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_line_shifts (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    old_address character varying(255),
    new_address character varying(255) NOT NULL,
    old_zone character varying(255),
    new_zone character varying(255),
    shift_charge numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    shift_date date NOT NULL,
    remarks text,
    processed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: client_line_shifts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_line_shifts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_line_shifts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_line_shifts_id_seq OWNED BY public.client_line_shifts.id;


--
-- Name: client_packages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_packages (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    package_id bigint NOT NULL,
    start_date date NOT NULL,
    end_date date,
    price numeric(12,2) NOT NULL,
    is_current boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_packages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_packages_id_seq OWNED BY public.client_packages.id;


--
-- Name: client_password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_password_reset_tokens (
    phone character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: client_promo_usage; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_promo_usage (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    promo_code_id bigint NOT NULL,
    bill_id bigint,
    discount_applied numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_promo_usage_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_promo_usage_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_promo_usage_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_promo_usage_id_seq OWNED BY public.client_promo_usage.id;


--
-- Name: client_reconnect_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_reconnect_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    action character varying(30) NOT NULL,
    trigger character varying(50) NOT NULL,
    result character varying(20) DEFAULT 'success'::character varying NOT NULL,
    details text,
    previous_status character varying(20),
    new_status character varying(20),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_reconnect_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_reconnect_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_reconnect_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_reconnect_logs_id_seq OWNED BY public.client_reconnect_logs.id;


--
-- Name: client_reconnections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_reconnections (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    disconnect_date date NOT NULL,
    reconnect_date date NOT NULL,
    reconnection_fee numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    disconnect_reason text,
    remarks text,
    processed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: client_reconnections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_reconnections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_reconnections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_reconnections_id_seq OWNED BY public.client_reconnections.id;


--
-- Name: client_recovery_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_recovery_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    recovered_by bigint NOT NULL,
    previous_status character varying(255) NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: client_recovery_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_recovery_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_recovery_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_recovery_logs_id_seq OWNED BY public.client_recovery_logs.id;


--
-- Name: client_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_schedules (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    action character varying(255) NOT NULL,
    scheduled_at timestamp(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    package_id bigint,
    mikrotik_profile character varying(255),
    note text,
    created_by bigint,
    executed_at timestamp(0) without time zone,
    execution_result text,
    company_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_schedules_id_seq OWNED BY public.client_schedules.id;


--
-- Name: client_service_charges; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_service_charges (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    charge_type character varying(100) NOT NULL,
    amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    description character varying(255),
    payment_method character varying(50),
    paid_by bigint,
    charge_date date,
    money_receipt_no character varying(255),
    transaction_id character varying(255),
    sender_number character varying(255),
    payment_method_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_service_charges_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_service_charges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_service_charges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_service_charges_id_seq OWNED BY public.client_service_charges.id;


--
-- Name: client_session_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_session_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    event_type character varying(255) NOT NULL,
    ip_address character varying(255),
    mac_address character varying(255),
    nas_ip character varying(255),
    session_id character varying(255),
    bytes_in bigint,
    bytes_out bigint,
    session_time integer,
    terminate_cause character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: client_session_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_session_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_session_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_session_logs_id_seq OWNED BY public.client_session_logs.id;


--
-- Name: client_status_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_status_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    old_status character varying(255) NOT NULL,
    new_status character varying(255) NOT NULL,
    reason text,
    changed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    source character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    company_id bigint
);


--
-- Name: client_status_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_status_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_status_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_status_logs_id_seq OWNED BY public.client_status_logs.id;


--
-- Name: client_timeline; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.client_timeline (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    event_type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    metadata json,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT client_timeline_event_type_check CHECK (((event_type)::text = ANY ((ARRAY['bill'::character varying, 'payment'::character varying, 'ticket'::character varying, 'status_change'::character varying, 'package_change'::character varying, 'line_shift'::character varying, 'reconnection'::character varying, 'note'::character varying])::text[])))
);


--
-- Name: client_timeline_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.client_timeline_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: client_timeline_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.client_timeline_id_seq OWNED BY public.client_timeline.id;


--
-- Name: clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clients (
    id bigint NOT NULL,
    user_id bigint,
    client_id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    phone character varying(255) NOT NULL,
    alt_phone character varying(255),
    address text NOT NULL,
    nid character varying(255),
    zone_id bigint,
    area_id bigint,
    package_id bigint,
    username character varying(255) NOT NULL,
    pppoe_password character varying(255),
    mac_address character varying(255),
    ip_address character varying(255),
    connection_date date,
    expire_date date,
    billing_date integer DEFAULT 1 NOT NULL,
    monthly_fee numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    due_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    connection_status character varying(255) DEFAULT 'offline'::character varying NOT NULL,
    reseller_id bigint,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    permanent_discount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    google_map_location character varying(255),
    mac_binding_enabled boolean DEFAULT false NOT NULL,
    custom_billing_cycle integer,
    connection_type character varying(255) DEFAULT 'pppoe'::character varying NOT NULL,
    nid_front_image character varying(255),
    nid_back_image character varying(255),
    google_map_lat numeric(10,8),
    google_map_lng numeric(11,8),
    uplink_device_id bigint,
    signup_fee numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    signup_fee_paid boolean DEFAULT false NOT NULL,
    payment_deadline integer DEFAULT 10 NOT NULL,
    auto_disconnect_days integer DEFAULT 15 NOT NULL,
    is_locked boolean DEFAULT false NOT NULL,
    lock_reason character varying(255),
    lock_date date,
    queue_max_limit character varying(255),
    queue_burst_limit character varying(255),
    youtube_bandwidth character varying(255),
    facebook_bandwidth character varying(255),
    auto_inactive_note text,
    last_inactive_date date,
    last_active_date date,
    bill_calculation_type character varying(255) DEFAULT 'auto'::character varying NOT NULL,
    last_seen_at timestamp(0) without time zone,
    current_rx_speed numeric(12,2),
    current_tx_speed numeric(12,2),
    session_uptime_seconds bigint,
    today_usage_bytes bigint DEFAULT '0'::bigint NOT NULL,
    month_usage_bytes bigint DEFAULT '0'::bigint NOT NULL,
    father_husband_name character varying(255),
    date_of_birth date,
    nearby_landmark character varying(255),
    billing_cycle character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    installation_charge numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    cable_length_meters numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    onu_required boolean DEFAULT false NOT NULL,
    router_required boolean DEFAULT false NOT NULL,
    preferred_payment_method character varying(255),
    technician_note text,
    application_id bigint,
    session_ip character varying(255),
    session_mac character varying(255),
    olt_device_id bigint,
    olt_port character varying(255),
    onu_model character varying(255),
    onu_status character varying(255) DEFAULT 'unknown'::character varying NOT NULL,
    onu_rx_power numeric(8,2),
    onu_tx_power numeric(8,2),
    onu_signal_quality character varying(255),
    onu_down_reason text,
    onu_last_down timestamp(0) without time zone,
    onu_last_check timestamp(0) without time zone,
    onu_temperature numeric(5,2),
    onu_firmware character varying(255),
    onu_uptime_seconds integer,
    mikrotik_device_id bigint,
    mikrotik_queue_id character varying(255),
    total_upload_bytes bigint DEFAULT '0'::bigint NOT NULL,
    total_download_bytes bigint DEFAULT '0'::bigint NOT NULL,
    bandwidth_last_updated timestamp(0) without time zone,
    session_uptime character varying(255),
    olt_id bigint,
    onu_serial character varying(255),
    onu_port character varying(255),
    onu_signal numeric(5,2),
    device_info character varying(255),
    uptime character varying(255),
    last_logout character varying(255),
    download_speed character varying(255),
    upload_speed character varying(255),
    download_usage character varying(255),
    upload_usage character varying(255),
    last_online timestamp(0) without time zone,
    family_group_id bigint,
    internet_quality_score integer DEFAULT 100 NOT NULL,
    churn_risk_score numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    lifetime_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    box_id bigint,
    portal_password character varying(255),
    remember_token character varying(100),
    portal_last_login timestamp(0) without time zone,
    connection_medium character varying(255) DEFAULT 'utp'::character varying NOT NULL,
    splitter_id bigint,
    splitter_port integer,
    deleted_reason character varying(255),
    deleted_by bigint,
    deleted_at_custom timestamp(0) without time zone,
    ipv6_address character varying(255),
    ipv6_prefix character varying(255),
    onu_id bigint,
    branch_id bigint,
    pop_id bigint,
    temp_deadline_date date,
    temp_deadline_month character varying(7),
    temp_deadline_reason text,
    temp_deadline_applied_by bigint,
    temp_deadline_applied_at timestamp(0) without time zone,
    sms_opt_out boolean DEFAULT false NOT NULL,
    is_free boolean DEFAULT false NOT NULL,
    free_reason character varying(255),
    onu_monthly_charge numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    client_type character varying(50) DEFAULT 'regular'::character varying,
    custom_status character varying(100),
    assigned_to bigint,
    effective_to date,
    non_effective_from date,
    profile_picture character varying(255),
    district_id bigint,
    upazila_id bigint,
    company_id bigint,
    occupation character varying(255),
    router_brand character varying(255),
    last_status_change timestamp(0) without time zone,
    onu_source character varying(20),
    technician_id bigint,
    gender character varying(20),
    mother_name character varying(255),
    fiber_code character varying(255),
    fiber_color character varying(255),
    splitter_number character varying(255),
    splitter_color character varying(255),
    onu_mac character varying(255),
    fiber_vendor character varying(255),
    fiber_core_type character varying(255),
    onu_brand character varying(255),
    mikrotik_profile_name character varying(255),
    agent_id bigint,
    commission_percent numeric(5,2),
    previous_mikrotik_profile character varying(255),
    fiber_required boolean DEFAULT true NOT NULL,
    auto_renewal boolean DEFAULT false NOT NULL,
    CONSTRAINT clients_bill_calculation_type_check CHECK (((bill_calculation_type)::text = ANY ((ARRAY['auto'::character varying, 'manual'::character varying])::text[]))),
    CONSTRAINT clients_connection_status_check CHECK (((connection_status)::text = ANY ((ARRAY['online'::character varying, 'offline'::character varying])::text[]))),
    CONSTRAINT clients_connection_type_check CHECK (((connection_type)::text = ANY ((ARRAY['pppoe'::character varying, 'queue'::character varying, 'invoice'::character varying])::text[]))),
    CONSTRAINT clients_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'suspended'::character varying, 'pending'::character varying, 'deleted'::character varying])::text[])))
);


--
-- Name: clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clients_id_seq OWNED BY public.clients.id;


--
-- Name: collection_targets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collection_targets (
    id bigint NOT NULL,
    collector_id bigint NOT NULL,
    zone_id bigint,
    month character varying(255) NOT NULL,
    target_amount numeric(15,2) NOT NULL,
    achieved_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: collection_targets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.collection_targets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collection_targets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.collection_targets_id_seq OWNED BY public.collection_targets.id;


--
-- Name: collection_transfers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collection_transfers (
    id bigint NOT NULL,
    transfer_no character varying(255) NOT NULL,
    from_user_id bigint NOT NULL,
    to_account_id bigint,
    amount numeric(15,2) NOT NULL,
    transfer_date date NOT NULL,
    remarks text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    verified_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    to_user_id bigint,
    CONSTRAINT collection_transfers_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'completed'::character varying])::text[])))
);


--
-- Name: collection_transfers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.collection_transfers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collection_transfers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.collection_transfers_id_seq OWNED BY public.collection_transfers.id;


--
-- Name: collections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.collections (
    id bigint NOT NULL,
    payment_id bigint NOT NULL,
    collected_by bigint NOT NULL,
    collection_date date NOT NULL,
    amount numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: collections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.collections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.collections_id_seq OWNED BY public.collections.id;


--
-- Name: companies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.companies (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    email character varying(255),
    phone character varying(255),
    alt_phone character varying(255),
    address character varying(255),
    logo character varying(255),
    logo_light character varying(255),
    favicon character varying(255),
    slogan character varying(255),
    website character varying(255),
    currency_symbol character varying(255) DEFAULT '৳'::character varying NOT NULL,
    currency_code character varying(255) DEFAULT 'BDT'::character varying NOT NULL,
    btrc_license character varying(255),
    bin_no character varying(255),
    trade_license character varying(255),
    invoice_footer text,
    terms_conditions text,
    sidebar_color character varying(255) DEFAULT '#1e3c72'::character varying NOT NULL,
    primary_color character varying(255) DEFAULT '#007bff'::character varying NOT NULL,
    ticker_label character varying(255),
    ticker_text text,
    login_animation_words text,
    login_animation_opacity integer DEFAULT 40 NOT NULL,
    max_clients integer DEFAULT 0 NOT NULL,
    enabled_features json,
    is_active boolean DEFAULT true NOT NULL,
    subscription_start date,
    subscription_end date,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    allowed_menus json
);


--
-- Name: companies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.companies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: companies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.companies_id_seq OWNED BY public.companies.id;


--
-- Name: company_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_profiles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    phone character varying(255),
    alt_phone character varying(255),
    address text,
    logo character varying(255),
    favicon character varying(255),
    website character varying(255),
    currency_symbol character varying(255) DEFAULT '৳'::character varying NOT NULL,
    currency_code character varying(255) DEFAULT 'BDT'::character varying NOT NULL,
    btrc_license character varying(255),
    bin_no character varying(255),
    trade_license character varying(255),
    invoice_footer text,
    terms_conditions text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: company_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_profiles_id_seq OWNED BY public.company_profiles.id;


--
-- Name: correction_audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.correction_audit_logs (
    id bigint NOT NULL,
    auditable_type character varying(100) NOT NULL,
    auditable_id bigint NOT NULL,
    action character varying(50) NOT NULL,
    before_state json,
    after_state json,
    performed_by bigint,
    performed_by_role character varying(50),
    ip_address inet,
    user_agent character varying(500),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: correction_audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.correction_audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: correction_audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.correction_audit_logs_id_seq OWNED BY public.correction_audit_logs.id;


--
-- Name: credit_debit_notes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credit_debit_notes (
    id bigint NOT NULL,
    note_no character varying(255) NOT NULL,
    client_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    amount numeric(12,2) NOT NULL,
    reason character varying(255) NOT NULL,
    description text,
    billing_month integer NOT NULL,
    billing_year integer NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    created_by bigint NOT NULL,
    ledger_entry_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT credit_debit_notes_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[]))),
    CONSTRAINT credit_debit_notes_type_check CHECK (((type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying])::text[])))
);


--
-- Name: credit_debit_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_debit_notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_debit_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_debit_notes_id_seq OWNED BY public.credit_debit_notes.id;


--
-- Name: cron_health_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cron_health_logs (
    id bigint NOT NULL,
    job_name character varying(100) NOT NULL,
    status character varying(20) DEFAULT 'success'::character varying NOT NULL,
    execution_time_ms integer,
    records_processed integer,
    error_message text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cron_health_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cron_health_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cron_health_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cron_health_logs_id_seq OWNED BY public.cron_health_logs.id;


--
-- Name: cron_job_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cron_job_logs (
    id bigint NOT NULL,
    job_name character varying(255) NOT NULL,
    job_class character varying(255),
    status character varying(255) DEFAULT 'started'::character varying NOT NULL,
    message text,
    metadata json,
    records_processed integer DEFAULT 0 NOT NULL,
    execution_time_ms integer DEFAULT 0 NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT cron_job_logs_status_check CHECK (((status)::text = ANY ((ARRAY['started'::character varying, 'completed'::character varying, 'failed'::character varying])::text[])))
);


--
-- Name: cron_job_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cron_job_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cron_job_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cron_job_logs_id_seq OWNED BY public.cron_job_logs.id;


--
-- Name: daily_cash_closes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.daily_cash_closes (
    id bigint NOT NULL,
    close_date date NOT NULL,
    staff_user_id bigint NOT NULL,
    opening_balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_collection numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_submitted numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    closing_balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    cash_in_hand numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    discrepancy numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    notes text,
    verified_by bigint,
    verified_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT daily_cash_closes_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'verified'::character varying, 'discrepancy'::character varying])::text[])))
);


--
-- Name: daily_cash_closes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.daily_cash_closes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: daily_cash_closes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.daily_cash_closes_id_seq OWNED BY public.daily_cash_closes.id;


--
-- Name: damaged_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.damaged_items (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    serial_id bigint,
    quantity integer DEFAULT 1 NOT NULL,
    damage_date date NOT NULL,
    damage_reason text NOT NULL,
    status character varying(255) DEFAULT 'reported'::character varying NOT NULL,
    reported_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT damaged_items_status_check CHECK (((status)::text = ANY ((ARRAY['reported'::character varying, 'claimed'::character varying, 'written_off'::character varying])::text[])))
);


--
-- Name: damaged_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.damaged_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: damaged_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.damaged_items_id_seq OWNED BY public.damaged_items.id;


--
-- Name: damaged_products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.damaged_products (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer NOT NULL,
    damage_date date NOT NULL,
    reason text,
    reported_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: damaged_products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.damaged_products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: damaged_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.damaged_products_id_seq OWNED BY public.damaged_products.id;


--
-- Name: departments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.departments (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: departments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.departments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: departments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.departments_id_seq OWNED BY public.departments.id;


--
-- Name: designations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.designations (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    department_id bigint,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: designations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.designations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: designations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.designations_id_seq OWNED BY public.designations.id;


--
-- Name: device_inventory; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.device_inventory (
    id bigint NOT NULL,
    assigned_to bigint,
    device_type character varying(255) NOT NULL,
    serial_number character varying(255) NOT NULL,
    model character varying(255),
    status character varying(255) DEFAULT 'available'::character varying NOT NULL,
    deployed_to_client bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT device_inventory_status_check CHECK (((status)::text = ANY ((ARRAY['available'::character varying, 'assigned'::character varying, 'deployed'::character varying, 'damaged'::character varying, 'lost'::character varying])::text[])))
);


--
-- Name: device_inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.device_inventory_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: device_inventory_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.device_inventory_id_seq OWNED BY public.device_inventory.id;


--
-- Name: device_operation_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.device_operation_logs (
    id bigint NOT NULL,
    device_type character varying(255) NOT NULL,
    device_id bigint NOT NULL,
    client_id bigint,
    operation character varying(255) NOT NULL,
    connection_type character varying(255),
    parameters json,
    result character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    result_message text,
    performed_by bigint,
    ip_address character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: device_operation_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.device_operation_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: device_operation_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.device_operation_logs_id_seq OWNED BY public.device_operation_logs.id;


--
-- Name: device_warranties; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.device_warranties (
    id bigint NOT NULL,
    device_type character varying(255) NOT NULL,
    device_id bigint NOT NULL,
    serial_number character varying(255),
    purchase_date date NOT NULL,
    warranty_start date NOT NULL,
    warranty_end date NOT NULL,
    vendor character varying(255),
    notes text,
    alert_sent boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: device_warranties_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.device_warranties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: device_warranties_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.device_warranties_id_seq OWNED BY public.device_warranties.id;


--
-- Name: discounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.discounts (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    value numeric(12,2) NOT NULL,
    valid_from date,
    valid_to date,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT discounts_type_check CHECK (((type)::text = ANY ((ARRAY['percentage'::character varying, 'fixed'::character varying])::text[])))
);


--
-- Name: discounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.discounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: discounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.discounts_id_seq OWNED BY public.discounts.id;


--
-- Name: districts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.districts (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    division character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_configured boolean DEFAULT false NOT NULL
);


--
-- Name: districts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.districts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: districts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.districts_id_seq OWNED BY public.districts.id;


--
-- Name: dropdown_options; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dropdown_options (
    id bigint NOT NULL,
    group_name character varying(100) NOT NULL,
    label character varying(255) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: dropdown_options_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dropdown_options_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dropdown_options_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dropdown_options_id_seq OWNED BY public.dropdown_options.id;


--
-- Name: due_reminders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.due_reminders (
    id bigint NOT NULL,
    bill_id bigint NOT NULL,
    client_id bigint NOT NULL,
    reminder_day integer NOT NULL,
    sms_sent boolean DEFAULT false NOT NULL,
    sent_at timestamp(0) without time zone,
    message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: due_reminders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.due_reminders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: due_reminders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.due_reminders_id_seq OWNED BY public.due_reminders.id;


--
-- Name: email_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_templates (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    subject character varying(255) NOT NULL,
    body text NOT NULL,
    variables json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: email_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_templates_id_seq OWNED BY public.email_templates.id;


--
-- Name: employee_collections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.employee_collections (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    collection_date date NOT NULL,
    total_amount numeric(12,2) NOT NULL,
    total_payments integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: employee_collections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.employee_collections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: employee_collections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.employee_collections_id_seq OWNED BY public.employee_collections.id;


--
-- Name: employee_performances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.employee_performances (
    id bigint NOT NULL,
    employee_id bigint NOT NULL,
    month integer NOT NULL,
    year integer NOT NULL,
    tickets_assigned integer DEFAULT 0 NOT NULL,
    tickets_resolved integer DEFAULT 0 NOT NULL,
    avg_resolution_time numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    sla_met integer DEFAULT 0 NOT NULL,
    sla_breached integer DEFAULT 0 NOT NULL,
    collection_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    collection_count integer DEFAULT 0 NOT NULL,
    rating_score numeric(3,2) DEFAULT '0'::numeric NOT NULL,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: employee_performances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.employee_performances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: employee_performances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.employee_performances_id_seq OWNED BY public.employee_performances.id;


--
-- Name: employees; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.employees (
    id bigint NOT NULL,
    user_id bigint,
    employee_id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    phone character varying(255) NOT NULL,
    address text,
    nid character varying(255),
    date_of_birth date,
    gender character varying(255),
    department_id bigint,
    designation_id bigint,
    joining_date date NOT NULL,
    basic_salary numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    bank_account character varying(255),
    bank_name character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    photo character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    zone_ids jsonb,
    nid_photo_front character varying(255),
    nid_photo_back character varying(255),
    cv_file character varying(255),
    company_id bigint,
    CONSTRAINT employees_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying, 'other'::character varying])::text[]))),
    CONSTRAINT employees_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'resigned'::character varying, 'terminated'::character varying])::text[])))
);


--
-- Name: employees_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.employees_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: employees_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.employees_id_seq OWNED BY public.employees.id;


--
-- Name: events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.events (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    event_date date NOT NULL,
    type character varying(255) DEFAULT 'event'::character varying NOT NULL,
    is_holiday boolean DEFAULT false NOT NULL,
    reseller_id bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT events_type_check CHECK (((type)::text = ANY ((ARRAY['holiday'::character varying, 'event'::character varying, 'maintenance'::character varying, 'other'::character varying])::text[])))
);


--
-- Name: events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.events_id_seq OWNED BY public.events.id;


--
-- Name: expense_heads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.expense_heads (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: expense_heads_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.expense_heads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: expense_heads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.expense_heads_id_seq OWNED BY public.expense_heads.id;


--
-- Name: expenses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.expenses (
    id bigint NOT NULL,
    expense_head_id bigint NOT NULL,
    account_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    expense_date date NOT NULL,
    description text,
    reference character varying(255),
    attachment character varying(255),
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    approval_status character varying(255) DEFAULT 'approved'::character varying NOT NULL,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT expenses_approval_status_check CHECK (((approval_status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: expenses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.expenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: expenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.expenses_id_seq OWNED BY public.expenses.id;


--
-- Name: export_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.export_jobs (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    data_types json NOT NULL,
    file_format character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    user_id bigint NOT NULL,
    file_path character varying(255),
    file_name character varying(255),
    file_size bigint,
    export_options json,
    export_summary json,
    error_message text,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT export_jobs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'processing'::character varying, 'completed'::character varying, 'failed'::character varying])::text[])))
);


--
-- Name: export_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.export_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: export_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.export_jobs_id_seq OWNED BY public.export_jobs.id;


--
-- Name: failed_job_retries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_job_retries (
    id bigint NOT NULL,
    job_class character varying(255) NOT NULL,
    payload json,
    attempt_count integer DEFAULT 0 NOT NULL,
    max_attempts integer DEFAULT 3 NOT NULL,
    last_error text,
    next_retry_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: failed_job_retries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_job_retries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_job_retries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_job_retries_id_seq OWNED BY public.failed_job_retries.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: family_group_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.family_group_members (
    id bigint NOT NULL,
    family_group_id bigint NOT NULL,
    client_id bigint NOT NULL,
    relationship character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: family_group_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.family_group_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: family_group_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.family_group_members_id_seq OWNED BY public.family_group_members.id;


--
-- Name: family_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.family_groups (
    id bigint NOT NULL,
    group_name character varying(255) NOT NULL,
    primary_client_id bigint NOT NULL,
    combined_discount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    combined_billing boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: family_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.family_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: family_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.family_groups_id_seq OWNED BY public.family_groups.id;


--
-- Name: fiber_codes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fiber_codes (
    id bigint NOT NULL,
    fiber_code character varying(255) NOT NULL,
    vendor_id bigint,
    fiber_type character varying(255) DEFAULT 'Single Mode'::character varying NOT NULL,
    start_meter integer DEFAULT 0 NOT NULL,
    end_meter integer NOT NULL,
    total_meter integer NOT NULL,
    remaining_meter integer NOT NULL,
    purchase_date date,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT fiber_codes_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'finished'::character varying])::text[])))
);


--
-- Name: fiber_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fiber_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fiber_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fiber_codes_id_seq OWNED BY public.fiber_codes.id;


--
-- Name: fiber_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fiber_histories (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    fiber_stock_id bigint NOT NULL,
    meter numeric(10,2) NOT NULL,
    details character varying(255),
    date date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    note text
);


--
-- Name: fiber_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fiber_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fiber_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fiber_histories_id_seq OWNED BY public.fiber_histories.id;


--
-- Name: fiber_stocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fiber_stocks (
    id bigint NOT NULL,
    vendor_id bigint NOT NULL,
    fiber_code character varying(255) NOT NULL,
    core integer NOT NULL,
    start_meter numeric(10,2) NOT NULL,
    end_meter numeric(10,2) NOT NULL,
    total_meter numeric(10,2) NOT NULL,
    remaining_meter numeric(10,2) NOT NULL,
    stock_date date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    brand_name character varying(255),
    notes text,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    fiber_type character varying(255) DEFAULT 'Single Mode'::character varying NOT NULL,
    name character varying(255),
    purchaser character varying(255),
    invoice_number character varying(255),
    per_meter_price numeric(10,2),
    total_cost numeric(12,2)
);


--
-- Name: fiber_stocks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fiber_stocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fiber_stocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fiber_stocks_id_seq OWNED BY public.fiber_stocks.id;


--
-- Name: fiber_usages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fiber_usages (
    id bigint NOT NULL,
    fiber_code_id bigint,
    used_meter integer NOT NULL,
    client_id bigint,
    technician_id bigint,
    note text,
    used_date date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    fiber_stock_id bigint
);


--
-- Name: fiber_usages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fiber_usages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fiber_usages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fiber_usages_id_seq OWNED BY public.fiber_usages.id;


--
-- Name: field_mapping_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.field_mapping_templates (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    source_software character varying(255),
    data_type character varying(255) NOT NULL,
    mapping json NOT NULL,
    created_by bigint NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    usage_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: field_mapping_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.field_mapping_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: field_mapping_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.field_mapping_templates_id_seq OWNED BY public.field_mapping_templates.id;


--
-- Name: firewall_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.firewall_rules (
    id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    chain character varying(255) NOT NULL,
    action character varying(255) NOT NULL,
    protocol character varying(255),
    src_address character varying(255),
    dst_address character varying(255),
    src_port character varying(255),
    dst_port character varying(255),
    in_interface character varying(255),
    out_interface character varying(255),
    comment character varying(255),
    disabled boolean DEFAULT false NOT NULL,
    mikrotik_id character varying(255),
    "position" integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: firewall_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.firewall_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: firewall_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.firewall_rules_id_seq OWNED BY public.firewall_rules.id;


--
-- Name: fraud_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fraud_alerts (
    id bigint NOT NULL,
    alert_type character varying(255) NOT NULL,
    trx_id character varying(255),
    description text,
    severity character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    reviewed_by bigint,
    reviewed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: fraud_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fraud_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fraud_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fraud_alerts_id_seq OWNED BY public.fraud_alerts.id;


--
-- Name: fund_transfers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fund_transfers (
    id bigint NOT NULL,
    from_account_id bigint NOT NULL,
    to_account_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    transfer_date date NOT NULL,
    description text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: fund_transfers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fund_transfers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fund_transfers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fund_transfers_id_seq OWNED BY public.fund_transfers.id;


--
-- Name: hotspot_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hotspot_templates (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    html_content text NOT NULL,
    css_content text,
    js_content text,
    logo_path character varying(255),
    background_image character varying(255),
    settings json,
    is_active boolean DEFAULT false NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    mikrotik_device_id bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: hotspot_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.hotspot_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hotspot_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.hotspot_templates_id_seq OWNED BY public.hotspot_templates.id;


--
-- Name: import_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_jobs (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    source_software character varying(255),
    file_name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    file_type character varying(255) NOT NULL,
    data_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    user_id bigint NOT NULL,
    field_mapping json,
    mapping_template_id bigint,
    total_rows integer DEFAULT 0 NOT NULL,
    valid_rows integer DEFAULT 0 NOT NULL,
    invalid_rows integer DEFAULT 0 NOT NULL,
    duplicate_rows integer DEFAULT 0 NOT NULL,
    imported_rows integer DEFAULT 0 NOT NULL,
    failed_rows integer DEFAULT 0 NOT NULL,
    skipped_rows integer DEFAULT 0 NOT NULL,
    validation_summary json,
    import_summary json,
    error_message text,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT import_jobs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'validating'::character varying, 'validated'::character varying, 'importing'::character varying, 'completed'::character varying, 'failed'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: import_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.import_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: import_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.import_jobs_id_seq OWNED BY public.import_jobs.id;


--
-- Name: import_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_logs (
    id bigint NOT NULL,
    import_job_id bigint NOT NULL,
    row_number integer NOT NULL,
    original_data json NOT NULL,
    mapped_data json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    entity_type character varying(255),
    entity_id bigint,
    validation_errors json,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT import_logs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'valid'::character varying, 'invalid'::character varying, 'duplicate'::character varying, 'imported'::character varying, 'failed'::character varying, 'skipped'::character varying])::text[])))
);


--
-- Name: import_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.import_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: import_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.import_logs_id_seq OWNED BY public.import_logs.id;


--
-- Name: income_heads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.income_heads (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: income_heads_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.income_heads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: income_heads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.income_heads_id_seq OWNED BY public.income_heads.id;


--
-- Name: incomes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.incomes (
    id bigint NOT NULL,
    income_head_id bigint NOT NULL,
    account_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    income_date date NOT NULL,
    description text,
    reference character varying(255),
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: incomes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.incomes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: incomes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.incomes_id_seq OWNED BY public.incomes.id;


--
-- Name: instrument_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.instrument_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    parent_id bigint,
    description character varying(255),
    company_id bigint,
    has_serial boolean DEFAULT true NOT NULL
);


--
-- Name: instrument_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.instrument_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: instrument_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.instrument_categories_id_seq OWNED BY public.instrument_categories.id;


--
-- Name: instrument_out_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.instrument_out_logs (
    id bigint NOT NULL,
    instrument_ids json NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    reason character varying(255) NOT NULL,
    issued_to character varying(255),
    technician_id bigint,
    remarks text,
    out_date date NOT NULL,
    description text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: instrument_out_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.instrument_out_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: instrument_out_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.instrument_out_logs_id_seq OWNED BY public.instrument_out_logs.id;


--
-- Name: instruments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.instruments (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    model character varying(255),
    brand character varying(255),
    serial_number character varying(255),
    category character varying(255),
    purchase_price numeric(12,2),
    purchase_date date,
    vendor_id bigint,
    condition character varying(255) DEFAULT 'good'::character varying NOT NULL,
    status character varying(255) DEFAULT 'available'::character varying NOT NULL,
    assigned_to character varying(255),
    warranty_expiry date,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    invoice_number character varying(255),
    sub_category character varying(255),
    company_id bigint,
    quantity integer DEFAULT 1 NOT NULL,
    purchaser character varying(255)
);


--
-- Name: instruments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.instruments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: instruments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.instruments_id_seq OWNED BY public.instruments.id;


--
-- Name: integrity_check_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.integrity_check_logs (
    id bigint NOT NULL,
    check_type character varying(50) DEFAULT 'full_billing_integrity'::character varying NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    clients_checked integer DEFAULT 0 NOT NULL,
    bills_checked integer DEFAULT 0 NOT NULL,
    issues_found integer DEFAULT 0 NOT NULL,
    auto_corrected integer DEFAULT 0 NOT NULL,
    requires_review integer DEFAULT 0 NOT NULL,
    details text,
    duration_seconds numeric(10,2),
    run_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: integrity_check_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.integrity_check_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: integrity_check_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.integrity_check_logs_id_seq OWNED BY public.integrity_check_logs.id;


--
-- Name: internet_quality_scores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.internet_quality_scores (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    score_date date NOT NULL,
    quality_score integer DEFAULT 100 NOT NULL,
    avg_speed_percent numeric(5,2) DEFAULT '100'::numeric NOT NULL,
    uptime_percent numeric(5,2) DEFAULT '100'::numeric NOT NULL,
    latency_score integer DEFAULT 100 NOT NULL,
    packet_loss_score integer DEFAULT 100 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: internet_quality_scores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.internet_quality_scores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: internet_quality_scores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.internet_quality_scores_id_seq OWNED BY public.internet_quality_scores.id;


--
-- Name: inventory_vendors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_vendors (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    company_name character varying(255),
    phone character varying(255),
    email character varying(255),
    address text,
    notes text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: inventory_vendors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_vendors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_vendors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_vendors_id_seq OWNED BY public.inventory_vendors.id;


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices (
    id bigint NOT NULL,
    invoice_no character varying(255) NOT NULL,
    bill_id bigint NOT NULL,
    client_id bigint NOT NULL,
    invoice_date date NOT NULL,
    total_amount numeric(12,2) NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoices_id_seq OWNED BY public.invoices.id;


--
-- Name: ip_addresses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ip_addresses (
    id bigint NOT NULL,
    ip_pool_id bigint NOT NULL,
    address character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'available'::character varying NOT NULL,
    client_id bigint,
    mac_address character varying(255),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: ip_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ip_addresses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ip_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ip_addresses_id_seq OWNED BY public.ip_addresses.id;


--
-- Name: ip_allocations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ip_allocations (
    id bigint NOT NULL,
    ip_pool_id bigint NOT NULL,
    ip_address character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'available'::character varying NOT NULL,
    client_id bigint,
    assigned_by bigint,
    mac_address character varying(255),
    hostname character varying(255),
    notes text,
    assigned_at timestamp(0) without time zone,
    released_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ip_allocations_status_check CHECK (((status)::text = ANY ((ARRAY['available'::character varying, 'assigned'::character varying, 'reserved'::character varying, 'disabled'::character varying])::text[])))
);


--
-- Name: ip_allocations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ip_allocations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ip_allocations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ip_allocations_id_seq OWNED BY public.ip_allocations.id;


--
-- Name: ip_pools; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ip_pools (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    network_address character varying(255) NOT NULL,
    subnet_mask character varying(255),
    cidr_prefix integer NOT NULL,
    ip_version character varying(255) DEFAULT 'ipv4'::character varying NOT NULL,
    gateway character varying(255),
    dns_primary character varying(255),
    dns_secondary character varying(255),
    pool_type character varying(255) DEFAULT 'pppoe'::character varying NOT NULL,
    branch_id bigint,
    zone_id bigint,
    pop_id bigint,
    mikrotik_device_id bigint,
    total_ips integer DEFAULT 0 NOT NULL,
    used_ips integer DEFAULT 0 NOT NULL,
    reserved_ips integer DEFAULT 0 NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT ip_pools_ip_version_check CHECK (((ip_version)::text = ANY ((ARRAY['ipv4'::character varying, 'ipv6'::character varying])::text[]))),
    CONSTRAINT ip_pools_pool_type_check CHECK (((pool_type)::text = ANY ((ARRAY['pppoe'::character varying, 'hotspot'::character varying, 'static'::character varying, 'dhcp'::character varying, 'management'::character varying])::text[])))
);


--
-- Name: ip_pools_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ip_pools_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ip_pools_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ip_pools_id_seq OWNED BY public.ip_pools.id;


--
-- Name: ip_restrictions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ip_restrictions (
    id bigint NOT NULL,
    user_id bigint,
    role character varying(255),
    ip_address character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    description character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ip_restrictions_type_check CHECK (((type)::text = ANY ((ARRAY['whitelist'::character varying, 'blacklist'::character varying])::text[])))
);


--
-- Name: ip_restrictions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ip_restrictions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ip_restrictions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ip_restrictions_id_seq OWNED BY public.ip_restrictions.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: job_failure_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_failure_logs (
    id bigint NOT NULL,
    job_name character varying(255) NOT NULL,
    job_class character varying(255) NOT NULL,
    payload json,
    exception text NOT NULL,
    attempts integer DEFAULT 1 NOT NULL,
    max_attempts integer DEFAULT 3 NOT NULL,
    status character varying(255) DEFAULT 'failed'::character varying NOT NULL,
    retried_at timestamp(0) without time zone,
    resolved_at timestamp(0) without time zone,
    resolved_by bigint,
    resolution_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT job_failure_logs_status_check CHECK (((status)::text = ANY ((ARRAY['failed'::character varying, 'retrying'::character varying, 'resolved'::character varying, 'ignored'::character varying])::text[])))
);


--
-- Name: job_failure_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.job_failure_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: job_failure_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.job_failure_logs_id_seq OWNED BY public.job_failure_logs.id;


--
-- Name: job_photos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_photos (
    id bigint NOT NULL,
    technician_job_id bigint NOT NULL,
    photo_type character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: job_photos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.job_photos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: job_photos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.job_photos_id_seq OWNED BY public.job_photos.id;


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: knowledge_base_articles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.knowledge_base_articles (
    id bigint NOT NULL,
    category_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    content text NOT NULL,
    audience character varying(255) DEFAULT 'all'::character varying NOT NULL,
    views integer DEFAULT 0 NOT NULL,
    is_published boolean DEFAULT true NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    panel_access json,
    company_id bigint
);


--
-- Name: knowledge_base_articles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.knowledge_base_articles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: knowledge_base_articles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.knowledge_base_articles_id_seq OWNED BY public.knowledge_base_articles.id;


--
-- Name: knowledge_base_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.knowledge_base_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    icon character varying(255) DEFAULT 'fas fa-book'::character varying NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: knowledge_base_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.knowledge_base_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: knowledge_base_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.knowledge_base_categories_id_seq OWNED BY public.knowledge_base_categories.id;


--
-- Name: leaves; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.leaves (
    id bigint NOT NULL,
    employee_id bigint NOT NULL,
    leave_type character varying(255) NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    total_days integer NOT NULL,
    reason text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by bigint,
    admin_remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT leaves_leave_type_check CHECK (((leave_type)::text = ANY ((ARRAY['casual'::character varying, 'sick'::character varying, 'annual'::character varying, 'unpaid'::character varying, 'other'::character varying])::text[]))),
    CONSTRAINT leaves_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: leaves_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.leaves_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: leaves_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.leaves_id_seq OWNED BY public.leaves.id;


--
-- Name: ledger_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ledger_entries (
    id bigint NOT NULL,
    entry_no character varying(50) NOT NULL,
    client_id bigint NOT NULL,
    entry_type character varying(255) NOT NULL,
    debit_credit character varying(255) NOT NULL,
    amount numeric(12,2) NOT NULL,
    running_balance numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    reference_type character varying(50),
    reference_id bigint,
    description character varying(500) NOT NULL,
    entry_date date NOT NULL,
    billing_month character varying(7),
    created_by bigint,
    created_by_role character varying(50),
    ip_address inet,
    is_reversal boolean DEFAULT false NOT NULL,
    reverses_entry_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ledger_entries_debit_credit_check CHECK (((debit_credit)::text = ANY ((ARRAY['debit'::character varying, 'credit'::character varying])::text[]))),
    CONSTRAINT ledger_entries_entry_type_check CHECK (((entry_type)::text = ANY ((ARRAY['bill'::character varying, 'payment'::character varying, 'adjustment'::character varying, 'reversal'::character varying, 'correction'::character varying, 'opening_balance'::character varying])::text[])))
);


--
-- Name: ledger_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ledger_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ledger_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ledger_entries_id_seq OWNED BY public.ledger_entries.id;


--
-- Name: live_pending_approvals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.live_pending_approvals (
    id bigint NOT NULL,
    approval_type character varying(255) NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    requested_by bigint,
    description text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: live_pending_approvals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.live_pending_approvals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: live_pending_approvals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.live_pending_approvals_id_seq OWNED BY public.live_pending_approvals.id;


--
-- Name: loan_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_payments (
    id bigint NOT NULL,
    loan_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    payment_date date NOT NULL,
    account_id bigint NOT NULL,
    remarks text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loan_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.loan_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: loan_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.loan_payments_id_seq OWNED BY public.loan_payments.id;


--
-- Name: loans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loans (
    id bigint NOT NULL,
    reference_no character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    party_name character varying(255) NOT NULL,
    party_phone character varying(255),
    amount numeric(15,2) NOT NULL,
    paid_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    remaining_amount numeric(15,2) NOT NULL,
    loan_date date NOT NULL,
    due_date date,
    account_id bigint NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    description text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT loans_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'partial'::character varying, 'completed'::character varying])::text[]))),
    CONSTRAINT loans_type_check CHECK (((type)::text = ANY ((ARRAY['given'::character varying, 'received'::character varying])::text[])))
);


--
-- Name: loans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.loans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: loans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.loans_id_seq OWNED BY public.loans.id;


--
-- Name: login_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.login_logs (
    id bigint NOT NULL,
    user_id bigint,
    email character varying(255),
    ip_address character varying(45) NOT NULL,
    user_agent character varying(255),
    status character varying(255) NOT NULL,
    failure_reason character varying(255),
    country character varying(255),
    city character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: login_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.login_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: login_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.login_logs_id_seq OWNED BY public.login_logs.id;


--
-- Name: mac_bindings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mac_bindings (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    mac_address character varying(255) NOT NULL,
    ip_address character varying(255) NOT NULL,
    comment text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mac_bindings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mac_bindings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mac_bindings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mac_bindings_id_seq OWNED BY public.mac_bindings.id;


--
-- Name: maintenance_modes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.maintenance_modes (
    id bigint NOT NULL,
    zone_id bigint,
    is_global boolean DEFAULT false NOT NULL,
    reason character varying(255) NOT NULL,
    pause_alerts boolean DEFAULT true NOT NULL,
    pause_auto_disconnect boolean DEFAULT true NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    scheduled_end timestamp(0) without time zone,
    ended_at timestamp(0) without time zone,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: maintenance_modes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.maintenance_modes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: maintenance_modes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.maintenance_modes_id_seq OWNED BY public.maintenance_modes.id;


--
-- Name: manager_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.manager_assignments (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    branch_id bigint,
    permissions json,
    allowed_zones json,
    can_manage_billing boolean DEFAULT true NOT NULL,
    can_manage_clients boolean DEFAULT true NOT NULL,
    can_manage_staff boolean DEFAULT false NOT NULL,
    can_view_reports boolean DEFAULT true NOT NULL,
    can_manage_resellers boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: manager_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.manager_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: manager_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.manager_assignments_id_seq OWNED BY public.manager_assignments.id;


--
-- Name: menu_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.menu_permissions (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    menu_key character varying(255) NOT NULL,
    submenu_key character varying(255),
    action_key character varying(255),
    allowed boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: menu_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.menu_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: menu_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.menu_permissions_id_seq OWNED BY public.menu_permissions.id;


--
-- Name: migration_router_snapshots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migration_router_snapshots (
    id bigint NOT NULL,
    migration_run_id bigint NOT NULL,
    device_id bigint,
    device_name character varying(255),
    device_host character varying(255),
    username character varying(255) NOT NULL,
    profile character varying(255) DEFAULT 'default'::character varying NOT NULL,
    password character varying(255),
    service character varying(255) DEFAULT 'pppoe'::character varying NOT NULL,
    disabled boolean DEFAULT false NOT NULL,
    comment text,
    remote_address character varying(255),
    last_logged_out timestamp(0) without time zone,
    billing_client_id bigint,
    match_status character varying(255) DEFAULT 'unmatched'::character varying NOT NULL,
    match_type character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: migration_router_snapshots_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migration_router_snapshots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migration_router_snapshots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migration_router_snapshots_id_seq OWNED BY public.migration_router_snapshots.id;


--
-- Name: migration_runs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migration_runs (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'in_progress'::character varying NOT NULL,
    current_phase integer DEFAULT 1 NOT NULL,
    phase1_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    phase2_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    phase3_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    phase4_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    phase5_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    phase1_summary json,
    phase2_summary json,
    phase3_summary json,
    phase4_summary json,
    phase5_summary json,
    import_job_id bigint,
    sync_run_id bigint,
    total_clients_imported integer DEFAULT 0 NOT NULL,
    total_bills_imported integer DEFAULT 0 NOT NULL,
    total_payments_imported integer DEFAULT 0 NOT NULL,
    total_routers_scanned integer DEFAULT 0 NOT NULL,
    total_router_users_found integer DEFAULT 0 NOT NULL,
    total_reconciled integer DEFAULT 0 NOT NULL,
    total_synced integer DEFAULT 0 NOT NULL,
    total_errors integer DEFAULT 0 NOT NULL,
    notes text,
    initiated_by bigint,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: migration_runs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migration_runs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migration_runs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migration_runs_id_seq OWNED BY public.migration_runs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: mikrotik_action_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_action_logs (
    id bigint NOT NULL,
    client_id bigint,
    device_id bigint,
    action character varying(255) NOT NULL,
    action_type character varying(255) NOT NULL,
    username character varying(255),
    router_ip character varying(255),
    trigger_source character varying(255) NOT NULL,
    triggered_by bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    retry_count integer DEFAULT 0 NOT NULL,
    error_message text,
    request_data json,
    response_data json,
    executed_at timestamp(0) without time zone,
    last_retry_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT mikrotik_action_logs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'success'::character varying, 'failed'::character varying, 'retrying'::character varying])::text[])))
);


--
-- Name: mikrotik_action_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_action_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_action_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_action_logs_id_seq OWNED BY public.mikrotik_action_logs.id;


--
-- Name: mikrotik_bandwidth_usage; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_bandwidth_usage (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    usage_date date NOT NULL,
    download_bytes bigint DEFAULT '0'::bigint NOT NULL,
    upload_bytes bigint DEFAULT '0'::bigint NOT NULL,
    peak_download_mbps numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    peak_upload_mbps numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_bandwidth_usage_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_bandwidth_usage_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_bandwidth_usage_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_bandwidth_usage_id_seq OWNED BY public.mikrotik_bandwidth_usage.id;


--
-- Name: mikrotik_devices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_devices (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    host character varying(255) NOT NULL,
    port integer DEFAULT 8728 NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    zone_id bigint,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    last_connected_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    last_cpu_load numeric(5,2),
    last_memory_usage numeric(5,2),
    last_temperature numeric(5,2),
    last_uptime_seconds integer,
    last_active_sessions integer,
    last_down_at timestamp(0) without time zone,
    last_up_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'unknown'::character varying NOT NULL,
    uptime character varying(255),
    last_checked_at timestamp(0) without time zone,
    model character varying(255),
    version character varying(255),
    cpu_load integer,
    memory_usage integer,
    total_clients integer DEFAULT 0 NOT NULL,
    latitude numeric(10,8),
    longitude numeric(11,8),
    last_sync timestamp(0) without time zone,
    last_sync_at timestamp(0) without time zone,
    last_sync_data json,
    active_users integer DEFAULT 0 NOT NULL,
    total_bandwidth_up integer DEFAULT 0 NOT NULL,
    total_bandwidth_down integer DEFAULT 0 NOT NULL,
    pop_id bigint,
    branch_id bigint,
    vpn_account_id bigint,
    www_port integer DEFAULT 80 NOT NULL,
    overwrite_ppp_comment boolean DEFAULT true NOT NULL,
    system_identity character varying(255),
    router_type character varying(255) DEFAULT 'core'::character varying NOT NULL,
    company_id bigint
);


--
-- Name: mikrotik_devices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_devices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_devices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_devices_id_seq OWNED BY public.mikrotik_devices.id;


--
-- Name: mikrotik_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_profiles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    rate_limit character varying(255),
    download_speed integer DEFAULT 0 NOT NULL,
    upload_speed integer DEFAULT 0 NOT NULL,
    speed_unit character varying(10) DEFAULT 'Mbps'::character varying NOT NULL,
    local_address character varying(255),
    remote_address character varying(255),
    source_device character varying(255),
    profile_type character varying(255) DEFAULT 'ppp'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    company_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_profiles_id_seq OWNED BY public.mikrotik_profiles.id;


--
-- Name: mikrotik_realtime_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_realtime_sessions (
    id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    client_id bigint,
    session_id character varying(255),
    username character varying(255) NOT NULL,
    ip_address character varying(255),
    mac_address character varying(255),
    bytes_in bigint DEFAULT '0'::bigint NOT NULL,
    bytes_out bigint DEFAULT '0'::bigint NOT NULL,
    uptime_seconds integer DEFAULT 0 NOT NULL,
    connected_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_realtime_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_realtime_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_realtime_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_realtime_sessions_id_seq OWNED BY public.mikrotik_realtime_sessions.id;


--
-- Name: mikrotik_router_stats; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_router_stats (
    id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    cpu_load integer DEFAULT 0 NOT NULL,
    free_memory bigint DEFAULT '0'::bigint NOT NULL,
    total_memory bigint DEFAULT '0'::bigint NOT NULL,
    free_hdd bigint DEFAULT '0'::bigint NOT NULL,
    total_hdd bigint DEFAULT '0'::bigint NOT NULL,
    uptime character varying(255),
    version character varying(255),
    board_name character varying(255),
    temperature integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_router_stats_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_router_stats_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_router_stats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_router_stats_id_seq OWNED BY public.mikrotik_router_stats.id;


--
-- Name: mikrotik_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_sessions (
    id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    client_id bigint,
    username character varying(255) NOT NULL,
    service character varying(255),
    caller_id character varying(255),
    address character varying(255),
    uptime_seconds bigint DEFAULT '0'::bigint NOT NULL,
    bytes_in bigint DEFAULT '0'::bigint NOT NULL,
    bytes_out bigint DEFAULT '0'::bigint NOT NULL,
    rate_in character varying(255),
    rate_out character varying(255),
    status character varying(255) DEFAULT 'online'::character varying NOT NULL,
    last_seen timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT mikrotik_sessions_status_check CHECK (((status)::text = ANY ((ARRAY['online'::character varying, 'offline'::character varying])::text[])))
);


--
-- Name: mikrotik_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_sessions_id_seq OWNED BY public.mikrotik_sessions.id;


--
-- Name: mikrotik_simulator_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_simulator_users (
    id bigint NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) DEFAULT 'secret123'::character varying NOT NULL,
    profile character varying(255) DEFAULT 'default'::character varying NOT NULL,
    service character varying(255) DEFAULT 'pppoe'::character varying NOT NULL,
    disabled boolean DEFAULT false NOT NULL,
    comment character varying(255),
    caller_id character varying(255),
    ip_address character varying(255),
    is_online boolean DEFAULT false NOT NULL,
    connected_at timestamp(0) without time zone,
    bytes_in bigint DEFAULT '0'::bigint NOT NULL,
    bytes_out bigint DEFAULT '0'::bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_simulator_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_simulator_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_simulator_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_simulator_users_id_seq OWNED BY public.mikrotik_simulator_users.id;


--
-- Name: mikrotik_sync_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_sync_items (
    id bigint NOT NULL,
    sync_run_id bigint NOT NULL,
    username character varying(255) NOT NULL,
    client_id bigint,
    diff_type character varying(255) NOT NULL,
    router_profile character varying(255),
    billing_profile character varying(255),
    router_service character varying(255),
    router_disabled boolean DEFAULT false NOT NULL,
    billing_status character varying(255),
    client_name character varying(255),
    client_code character varying(255),
    proposed_action character varying(255),
    action_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    action_message text,
    action_applied_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_sync_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_sync_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_sync_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_sync_items_id_seq OWNED BY public.mikrotik_sync_items.id;


--
-- Name: mikrotik_sync_runs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_sync_runs (
    id bigint NOT NULL,
    device_id bigint NOT NULL,
    status character varying(255) DEFAULT 'running'::character varying NOT NULL,
    mode character varying(255) DEFAULT 'dry_run'::character varying NOT NULL,
    initiated_by bigint,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    router_user_count integer DEFAULT 0 NOT NULL,
    billing_user_count integer DEFAULT 0 NOT NULL,
    synced_count integer DEFAULT 0 NOT NULL,
    only_on_router_count integer DEFAULT 0 NOT NULL,
    only_in_billing_count integer DEFAULT 0 NOT NULL,
    mismatch_count integer DEFAULT 0 NOT NULL,
    actions_applied integer DEFAULT 0 NOT NULL,
    actions_failed integer DEFAULT 0 NOT NULL,
    error_message text,
    summary json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_sync_runs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_sync_runs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_sync_runs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_sync_runs_id_seq OWNED BY public.mikrotik_sync_runs.id;


--
-- Name: mikrotik_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mikrotik_users (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    mikrotik_id character varying(255),
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    profile character varying(255),
    service character varying(255) DEFAULT 'pppoe'::character varying NOT NULL,
    is_disabled boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mikrotik_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mikrotik_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mikrotik_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mikrotik_users_id_seq OWNED BY public.mikrotik_users.id;


--
-- Name: minute_packages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.minute_packages (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    duration_minutes integer NOT NULL,
    price numeric(12,2) NOT NULL,
    bandwidth_up_mbps integer,
    bandwidth_down_mbps integer,
    connection_type character varying(255) DEFAULT 'hotspot'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT minute_packages_connection_type_check CHECK (((connection_type)::text = ANY ((ARRAY['pppoe'::character varying, 'hotspot'::character varying])::text[])))
);


--
-- Name: minute_packages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.minute_packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: minute_packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.minute_packages_id_seq OWNED BY public.minute_packages.id;


--
-- Name: minute_sales; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.minute_sales (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    minute_package_id bigint NOT NULL,
    total_minutes integer NOT NULL,
    used_minutes integer DEFAULT 0 NOT NULL,
    remaining_minutes integer DEFAULT 0 NOT NULL,
    amount_paid numeric(12,2) NOT NULL,
    purchased_at timestamp(0) without time zone NOT NULL,
    expires_at timestamp(0) without time zone,
    activated_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    sold_by bigint,
    reseller_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT minute_sales_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'expired'::character varying, 'exhausted'::character varying, 'refunded'::character varying])::text[])))
);


--
-- Name: minute_sales_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.minute_sales_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: minute_sales_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.minute_sales_id_seq OWNED BY public.minute_sales.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: month_closes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.month_closes (
    id bigint NOT NULL,
    billing_month integer NOT NULL,
    billing_year integer NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    total_billed numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    total_collected numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    total_due numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    total_advance numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    active_clients integer DEFAULT 0 NOT NULL,
    new_connections integer DEFAULT 0 NOT NULL,
    disconnections integer DEFAULT 0 NOT NULL,
    closed_by bigint,
    closed_at timestamp(0) without time zone,
    notes text,
    summary_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT month_closes_status_check CHECK (((status)::text = ANY ((ARRAY['open'::character varying, 'closed'::character varying, 'locked'::character varying])::text[])))
);


--
-- Name: month_closes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.month_closes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: month_closes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.month_closes_id_seq OWNED BY public.month_closes.id;


--
-- Name: nat_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.nat_rules (
    id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    chain character varying(255) DEFAULT 'srcnat'::character varying NOT NULL,
    action character varying(255) NOT NULL,
    protocol character varying(255),
    src_address character varying(255),
    dst_address character varying(255),
    src_port character varying(255),
    dst_port character varying(255),
    to_addresses character varying(255),
    to_ports character varying(255),
    in_interface character varying(255),
    out_interface character varying(255),
    comment character varying(255),
    disabled boolean DEFAULT false NOT NULL,
    mikrotik_id character varying(255),
    "position" integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: nat_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.nat_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: nat_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.nat_rules_id_seq OWNED BY public.nat_rules.id;


--
-- Name: network_event_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.network_event_logs (
    id bigint NOT NULL,
    event_type character varying(50) NOT NULL,
    severity character varying(20) DEFAULT 'info'::character varying NOT NULL,
    mikrotik_device_id bigint,
    olt_device_id bigint,
    zone_id bigint,
    area_id bigint,
    description text,
    affected_clients json,
    affected_count integer DEFAULT 0 NOT NULL,
    is_resolved boolean DEFAULT false NOT NULL,
    resolved_at timestamp(0) without time zone,
    resolution_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    source character varying(255),
    message text,
    metadata json
);


--
-- Name: network_event_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.network_event_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: network_event_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.network_event_logs_id_seq OWNED BY public.network_event_logs.id;


--
-- Name: new_connection_applications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.new_connection_applications (
    id bigint NOT NULL,
    application_number character varying(255) NOT NULL,
    full_name_bangla character varying(255),
    full_name_english character varying(255) NOT NULL,
    father_husband_name character varying(255),
    mother_name character varying(255),
    date_of_birth date,
    gender character varying(255) DEFAULT 'male'::character varying NOT NULL,
    mobile_number character varying(255) NOT NULL,
    alternative_mobile character varying(255),
    email character varying(255),
    nid_number character varying(255) NOT NULL,
    nid_front_image character varying(255),
    nid_back_image character varying(255),
    nid_verification_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    division character varying(255),
    district character varying(255),
    thana_upazila character varying(255),
    union_ward character varying(255),
    zone_id bigint,
    sub_zone_id bigint,
    house_road_floor text,
    latitude numeric(10,8),
    longitude numeric(11,8),
    nearby_landmark character varying(255),
    connection_type character varying(255) DEFAULT 'home'::character varying NOT NULL,
    package_id bigint,
    bandwidth_speed character varying(255),
    billing_cycle character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    installation_charge numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    onu_required boolean DEFAULT false NOT NULL,
    router_required boolean DEFAULT false NOT NULL,
    cable_length_meters integer,
    ip_type character varying(255) DEFAULT 'dynamic'::character varying NOT NULL,
    technician_id bigint,
    technician_assigned_area character varying(255),
    technician_note text,
    installation_feasibility boolean DEFAULT true NOT NULL,
    house_owner_permission character varying(255),
    installation_place_photo character varying(255),
    customer_signature character varying(255),
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    admin_note text,
    rejection_reason text,
    correction_request text,
    reviewed_by bigint,
    reviewed_at timestamp(0) without time zone,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    created_client_id bigint,
    mikrotik_username character varying(255),
    mikrotik_device_id bigint,
    mikrotik_created boolean DEFAULT false NOT NULL,
    mikrotik_creation_log text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    signup_fee numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    payment_method character varying(255),
    next_billing_date date,
    onu_model character varying(255),
    onu_serial character varying(255),
    olt_port character varying(255),
    olt_device_id bigint,
    device_notes text,
    preferred_connection_date character varying(255),
    preferred_time_slot character varying(255),
    connection_medium character varying(255) DEFAULT 'utp'::character varying NOT NULL,
    district_id bigint,
    upazila_id bigint,
    company_id bigint,
    fiber_code character varying(255),
    fiber_color character varying(255),
    splitter_number character varying(255),
    splitter_color character varying(255),
    onu_mac character varying(255),
    fiber_vendor character varying(255),
    fiber_core_type character varying(255),
    onu_brand character varying(255),
    expire_date date,
    mikrotik_profile_name character varying(255),
    box_id bigint,
    fiber_required boolean DEFAULT true NOT NULL,
    occupation character varying(100),
    agent_id bigint,
    commission_percent numeric(5,2),
    is_free boolean DEFAULT false NOT NULL,
    free_reason character varying(255),
    CONSTRAINT new_connection_applications_billing_cycle_check CHECK (((billing_cycle)::text = ANY ((ARRAY['monthly'::character varying, 'quarterly'::character varying, 'half_yearly'::character varying, 'yearly'::character varying, 'custom'::character varying])::text[]))),
    CONSTRAINT new_connection_applications_connection_type_check CHECK (((connection_type)::text = ANY (ARRAY['home'::text, 'corporate'::text, 'PPPoE'::text, 'Static IP'::text, 'DHCP'::text, 'pppoe'::text, 'static'::text, 'dhcp'::text]))),
    CONSTRAINT new_connection_applications_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying, 'other'::character varying])::text[]))),
    CONSTRAINT new_connection_applications_ip_type_check CHECK (((ip_type)::text = ANY ((ARRAY['dynamic'::character varying, 'static'::character varying])::text[]))),
    CONSTRAINT new_connection_applications_nid_verification_status_check CHECK (((nid_verification_status)::text = ANY ((ARRAY['pending'::character varying, 'verified'::character varying, 'rejected'::character varying])::text[]))),
    CONSTRAINT new_connection_applications_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'submitted'::character varying, 'under_review'::character varying, 'need_correction'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: new_connection_applications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.new_connection_applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: new_connection_applications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.new_connection_applications_id_seq OWNED BY public.new_connection_applications.id;


--
-- Name: nid_verifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.nid_verifications (
    id bigint NOT NULL,
    application_id bigint NOT NULL,
    nid_number character varying(255) NOT NULL,
    verification_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    verification_note text,
    verified_by bigint,
    verified_at timestamp(0) without time zone,
    verification_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT nid_verifications_verification_status_check CHECK (((verification_status)::text = ANY ((ARRAY['pending'::character varying, 'verified'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: nid_verifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.nid_verifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: nid_verifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.nid_verifications_id_seq OWNED BY public.nid_verifications.id;


--
-- Name: notification_queue; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notification_queue (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    channel character varying(255) NOT NULL,
    recipient_type character varying(255) NOT NULL,
    recipient_id bigint,
    recipient_phone character varying(255),
    recipient_email character varying(255),
    title character varying(255) NOT NULL,
    message text NOT NULL,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    metadata json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    sent_at timestamp(0) without time zone,
    scheduled_at timestamp(0) without time zone,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: notification_queue_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notification_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notification_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notification_queue_id_seq OWNED BY public.notification_queue.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    data jsonb,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: olt_devices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.olt_devices (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    ip_address character varying(255) NOT NULL,
    port integer DEFAULT 23 NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    vendor character varying(255) DEFAULT 'huawei'::character varying NOT NULL,
    model character varying(255),
    location character varying(255),
    total_ports integer DEFAULT 8 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_sync timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    snmp_community character varying(255) DEFAULT 'public'::character varying,
    snmp_write_community character varying(255) DEFAULT 'private'::character varying,
    snmp_port integer DEFAULT 161,
    snmp_enabled boolean DEFAULT false NOT NULL,
    pop_id bigint,
    branch_id bigint,
    company_id bigint,
    connection_type character varying(20) DEFAULT 'telnet'::character varying NOT NULL,
    ssh_port integer,
    telnet_port integer,
    http_port integer,
    status character varying(20) DEFAULT 'unknown'::character varying NOT NULL,
    pon_type character varying(10),
    cpu_usage double precision,
    memory_usage double precision,
    temperature double precision,
    system_description text,
    firmware_version character varying(255),
    uptime_seconds bigint
);


--
-- Name: olt_devices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.olt_devices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: olt_devices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.olt_devices_id_seq OWNED BY public.olt_devices.id;


--
-- Name: olt_ports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.olt_ports (
    id bigint NOT NULL,
    olt_device_id bigint NOT NULL,
    port_no character varying(255) NOT NULL,
    total_onu integer DEFAULT 0 NOT NULL,
    online_onu integer DEFAULT 0 NOT NULL,
    offline_onu integer DEFAULT 0 NOT NULL,
    last_polled timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: olt_ports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.olt_ports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: olt_ports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.olt_ports_id_seq OWNED BY public.olt_ports.id;


--
-- Name: olt_snmp_stats; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.olt_snmp_stats (
    id bigint NOT NULL,
    olt_device_id bigint NOT NULL,
    company_id bigint,
    cpu_usage double precision,
    memory_usage double precision,
    temperature double precision,
    uptime_seconds bigint,
    total_onu integer DEFAULT 0 NOT NULL,
    online_onu integer DEFAULT 0 NOT NULL,
    offline_onu integer DEFAULT 0 NOT NULL,
    avg_rx_power double precision,
    polled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: olt_snmp_stats_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.olt_snmp_stats_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: olt_snmp_stats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.olt_snmp_stats_id_seq OWNED BY public.olt_snmp_stats.id;


--
-- Name: olt_traffic_stats; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.olt_traffic_stats (
    id bigint NOT NULL,
    olt_device_id bigint NOT NULL,
    company_id bigint,
    port_no integer NOT NULL,
    in_octets bigint DEFAULT '0'::bigint NOT NULL,
    out_octets bigint DEFAULT '0'::bigint NOT NULL,
    in_rate_bps bigint DEFAULT '0'::bigint NOT NULL,
    out_rate_bps bigint DEFAULT '0'::bigint NOT NULL,
    total_onu integer DEFAULT 0 NOT NULL,
    online_onu integer DEFAULT 0 NOT NULL,
    polled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: olt_traffic_stats_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.olt_traffic_stats_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: olt_traffic_stats_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.olt_traffic_stats_id_seq OWNED BY public.olt_traffic_stats.id;


--
-- Name: online_client_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.online_client_history (
    id bigint NOT NULL,
    company_id bigint,
    online_count integer DEFAULT 0 NOT NULL,
    recorded_at timestamp(0) without time zone NOT NULL
);


--
-- Name: online_client_history_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.online_client_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: online_client_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.online_client_history_id_seq OWNED BY public.online_client_history.id;


--
-- Name: onu_alarm_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.onu_alarm_logs (
    id bigint NOT NULL,
    olt_device_id bigint NOT NULL,
    onu_id bigint,
    serial_number character varying(50),
    alarm_type character varying(50) NOT NULL,
    severity character varying(20) DEFAULT 'warning'::character varying NOT NULL,
    description text,
    rx_power numeric(8,2),
    tx_power numeric(8,2),
    is_resolved boolean DEFAULT false NOT NULL,
    resolved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: onu_alarm_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.onu_alarm_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: onu_alarm_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.onu_alarm_logs_id_seq OWNED BY public.onu_alarm_logs.id;


--
-- Name: onu_alarms; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.onu_alarms (
    id bigint NOT NULL,
    onu_device_id bigint NOT NULL,
    alarm_type character varying(255) NOT NULL,
    description text,
    severity character varying(255) DEFAULT 'warning'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    detected_at timestamp(0) without time zone,
    cleared_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: onu_alarms_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.onu_alarms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: onu_alarms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.onu_alarms_id_seq OWNED BY public.onu_alarms.id;


--
-- Name: onu_devices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.onu_devices (
    id bigint NOT NULL,
    olt_device_id bigint NOT NULL,
    client_id bigint,
    onu_serial character varying(255) NOT NULL,
    port character varying(255) NOT NULL,
    ont_id character varying(255),
    status character varying(255) DEFAULT 'unknown'::character varying NOT NULL,
    rx_power numeric(8,2),
    tx_power numeric(8,2),
    description character varying(255),
    last_down_reason character varying(255),
    last_down_at timestamp(0) without time zone,
    last_seen timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    temperature numeric(5,2),
    company_id bigint,
    CONSTRAINT onu_devices_status_check CHECK (((status)::text = ANY ((ARRAY['online'::character varying, 'offline'::character varying, 'unknown'::character varying])::text[])))
);


--
-- Name: onu_devices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.onu_devices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: onu_devices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.onu_devices_id_seq OWNED BY public.onu_devices.id;


--
-- Name: onu_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.onu_events (
    id bigint NOT NULL,
    onu_device_id bigint NOT NULL,
    event_type character varying(255) NOT NULL,
    rx_power numeric(8,2),
    message text,
    detected_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT onu_events_event_type_check CHECK (((event_type)::text = ANY ((ARRAY['LOS'::character varying, 'LOF'::character varying, 'ONLINE'::character varying, 'OFFLINE'::character varying, 'POWER_LOW'::character varying, 'POWER_CRITICAL'::character varying])::text[])))
);


--
-- Name: onu_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.onu_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: onu_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.onu_events_id_seq OWNED BY public.onu_events.id;


--
-- Name: onu_provision_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.onu_provision_logs (
    id bigint NOT NULL,
    onu_id bigint,
    olt_device_id bigint NOT NULL,
    client_id bigint,
    serial_number character varying(255) NOT NULL,
    action character varying(255) NOT NULL,
    pon_port character varying(255),
    onu_number integer,
    service_profile character varying(255),
    line_profile character varying(255),
    vlan character varying(255),
    result character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    result_message text,
    command_output text,
    performed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: onu_provision_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.onu_provision_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: onu_provision_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.onu_provision_logs_id_seq OWNED BY public.onu_provision_logs.id;


--
-- Name: onu_reboot_history; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.onu_reboot_history (
    id bigint NOT NULL,
    onu_id bigint NOT NULL,
    client_id bigint,
    serial_number character varying(50),
    reboot_type character varying(30) DEFAULT 'manual'::character varying NOT NULL,
    initiated_by character varying(30),
    initiated_by_user_id bigint,
    reason text,
    result character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    result_message text,
    rebooted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: onu_reboot_history_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.onu_reboot_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: onu_reboot_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.onu_reboot_history_id_seq OWNED BY public.onu_reboot_history.id;


--
-- Name: onus; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.onus (
    id bigint NOT NULL,
    olt_device_id bigint NOT NULL,
    client_id bigint,
    serial_number character varying(255) NOT NULL,
    pon_port character varying(255),
    onu_number integer,
    model character varying(255),
    description character varying(255),
    status character varying(255) DEFAULT 'offline'::character varying NOT NULL,
    rx_power double precision,
    tx_power double precision,
    temperature double precision,
    uptime_seconds integer,
    signal_quality character varying(255),
    mac_address character varying(255),
    ip_address character varying(255),
    vlan character varying(255),
    service_profile character varying(255),
    line_profile character varying(255),
    down_reason character varying(255),
    last_online timestamp(0) without time zone,
    last_offline timestamp(0) without time zone,
    provisioned_at timestamp(0) without time zone,
    provisioned_by bigint,
    is_provisioned boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    distance double precision,
    tx_bias double precision,
    voltage double precision
);


--
-- Name: onus_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.onus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: onus_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.onus_id_seq OWNED BY public.onus.id;


--
-- Name: otp_verifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.otp_verifications (
    id bigint NOT NULL,
    phone character varying(255) NOT NULL,
    otp_code character varying(255) NOT NULL,
    purpose character varying(255) DEFAULT 'registration'::character varying NOT NULL,
    is_verified boolean DEFAULT false NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    verified_at timestamp(0) without time zone,
    ip_address character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT otp_verifications_purpose_check CHECK (((purpose)::text = ANY ((ARRAY['registration'::character varying, 'login'::character varying, 'password_reset'::character varying, 'payment'::character varying])::text[])))
);


--
-- Name: otp_verifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.otp_verifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: otp_verifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.otp_verifications_id_seq OWNED BY public.otp_verifications.id;


--
-- Name: package_change_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.package_change_requests (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    current_package_id bigint NOT NULL,
    requested_package_id bigint NOT NULL,
    request_date date NOT NULL,
    effective_date date,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    client_remarks text,
    admin_remarks text,
    approved_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT package_change_requests_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: package_change_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.package_change_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: package_change_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.package_change_requests_id_seq OWNED BY public.package_change_requests.id;


--
-- Name: package_speeds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.package_speeds (
    id bigint NOT NULL,
    package_id bigint NOT NULL,
    time_from character varying(255) NOT NULL,
    time_to character varying(255) NOT NULL,
    download_speed integer NOT NULL,
    upload_speed integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: package_speeds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.package_speeds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: package_speeds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.package_speeds_id_seq OWNED BY public.package_speeds.id;


--
-- Name: packages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.packages (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    price numeric(12,2) NOT NULL,
    bandwidth character varying(255) NOT NULL,
    download_speed integer NOT NULL,
    upload_speed integer NOT NULL,
    speed_unit character varying(255) DEFAULT 'Mbps'::character varying NOT NULL,
    connection_type character varying(255) DEFAULT 'PPPoE'::character varying NOT NULL,
    validity_days integer DEFAULT 30 NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    show_in_public boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint,
    mikrotik_profile character varying(255),
    CONSTRAINT packages_connection_type_check CHECK (((connection_type)::text = ANY ((ARRAY['PPPoE'::character varying, 'Static IP'::character varying, 'Hotspot'::character varying, 'DHCP'::character varying])::text[])))
);


--
-- Name: packages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.packages_id_seq OWNED BY public.packages.id;


--
-- Name: password_policies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_policies (
    id bigint NOT NULL,
    min_length integer DEFAULT 8 NOT NULL,
    require_uppercase boolean DEFAULT true NOT NULL,
    require_lowercase boolean DEFAULT true NOT NULL,
    require_numbers boolean DEFAULT true NOT NULL,
    require_special boolean DEFAULT false NOT NULL,
    password_expiry_days integer DEFAULT 0 NOT NULL,
    max_login_attempts integer DEFAULT 5 NOT NULL,
    lockout_duration_minutes integer DEFAULT 30 NOT NULL,
    enforce_2fa_admin boolean DEFAULT false NOT NULL,
    enforce_2fa_all boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: password_policies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.password_policies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: password_policies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.password_policies_id_seq OWNED BY public.password_policies.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payment_audit_trail; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_audit_trail (
    id bigint NOT NULL,
    payment_id bigint NOT NULL,
    action character varying(255) NOT NULL,
    performed_by bigint,
    data json,
    ip_address character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payment_audit_trail_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_audit_trail_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_audit_trail_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_audit_trail_id_seq OWNED BY public.payment_audit_trail.id;


--
-- Name: payment_corrections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_corrections (
    id bigint NOT NULL,
    correction_no character varying(50) NOT NULL,
    payment_id bigint NOT NULL,
    client_id bigint NOT NULL,
    correction_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    original_amount numeric(12,2) NOT NULL,
    original_bill_id bigint,
    original_transaction_id character varying(100),
    new_amount numeric(12,2),
    new_bill_id bigint,
    reversal_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    duplicate_of_payment_id bigint,
    duplicate_reason character varying(255),
    reason text NOT NULL,
    admin_notes text,
    requested_by bigint,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    ip_address inet,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT payment_corrections_correction_type_check CHECK (((correction_type)::text = ANY ((ARRAY['duplicate'::character varying, 'reversal'::character varying, 'amount_correction'::character varying, 'reallocation'::character varying])::text[]))),
    CONSTRAINT payment_corrections_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: payment_corrections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_corrections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_corrections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_corrections_id_seq OWNED BY public.payment_corrections.id;


--
-- Name: payment_fraud_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_fraud_alerts (
    id bigint NOT NULL,
    payment_id bigint NOT NULL,
    alert_type character varying(255) NOT NULL,
    transaction_id character varying(255),
    amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    message text NOT NULL,
    is_resolved boolean DEFAULT false NOT NULL,
    resolved_by bigint,
    resolved_at timestamp(0) without time zone,
    resolution_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT payment_fraud_alerts_alert_type_check CHECK (((alert_type)::text = ANY ((ARRAY['duplicate_trxid'::character varying, 'suspicious_amount'::character varying, 'unusual_pattern'::character varying])::text[])))
);


--
-- Name: payment_fraud_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_fraud_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_fraud_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_fraud_alerts_id_seq OWNED BY public.payment_fraud_alerts.id;


--
-- Name: payment_gateway_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_gateway_transactions (
    id bigint NOT NULL,
    transaction_id character varying(255) NOT NULL,
    gateway character varying(255) NOT NULL,
    client_id bigint NOT NULL,
    bill_id bigint,
    amount numeric(12,2) NOT NULL,
    status character varying(255) DEFAULT 'initiated'::character varying NOT NULL,
    gateway_response json,
    verified_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payment_gateway_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_gateway_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_gateway_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_gateway_transactions_id_seq OWNED BY public.payment_gateway_transactions.id;


--
-- Name: payment_links; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_links (
    id bigint NOT NULL,
    token character varying(64) NOT NULL,
    client_id bigint NOT NULL,
    bill_id bigint,
    amount numeric(15,2) NOT NULL,
    description character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    paid_at timestamp(0) without time zone,
    payment_id bigint,
    payment_method character varying(255),
    created_by bigint,
    sent_via character varying(255),
    view_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT payment_links_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'paid'::character varying, 'expired'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: payment_links_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_links_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_links_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_links_id_seq OWNED BY public.payment_links.id;


--
-- Name: payment_method_perms; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_method_perms (
    id bigint NOT NULL,
    company_id bigint,
    role_id bigint,
    user_id bigint,
    payment_method_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payment_method_perms_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_method_perms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_method_perms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_method_perms_id_seq OWNED BY public.payment_method_perms.id;


--
-- Name: payment_methods; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_methods (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: payment_methods_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_methods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_methods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_methods_id_seq OWNED BY public.payment_methods.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    payment_no character varying(255) NOT NULL,
    client_id bigint NOT NULL,
    bill_id bigint,
    amount numeric(12,2) NOT NULL,
    payment_method_id bigint,
    payment_date date NOT NULL,
    transaction_id character varying(255),
    remarks text,
    collected_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    submitted_to_admin boolean DEFAULT false NOT NULL,
    submitted_at timestamp(0) without time zone,
    submission_id bigint,
    notes text,
    is_reversed boolean DEFAULT false NOT NULL,
    is_duplicate boolean DEFAULT false NOT NULL,
    duplicate_of_id bigint,
    request_token character varying(64),
    status character varying(255) DEFAULT 'approved'::character varying NOT NULL,
    source character varying(255) DEFAULT 'admin'::character varying NOT NULL,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    company_id bigint,
    money_receipt_no character varying(50)
);


--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: permission_audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permission_audit_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    action character varying(50) NOT NULL,
    entity_type character varying(50),
    entity_id bigint,
    data json,
    ip_address character varying(45),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permission_audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permission_audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permission_audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permission_audit_logs_id_seq OWNED BY public.permission_audit_logs.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: planned_maintenances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.planned_maintenances (
    id bigint NOT NULL,
    zone_id bigint,
    title character varying(255) NOT NULL,
    description text,
    scheduled_start timestamp(0) without time zone NOT NULL,
    scheduled_end timestamp(0) without time zone NOT NULL,
    notification_sent boolean DEFAULT false NOT NULL,
    is_completed boolean DEFAULT false NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: planned_maintenances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.planned_maintenances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: planned_maintenances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.planned_maintenances_id_seq OWNED BY public.planned_maintenances.id;


--
-- Name: pops; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pops (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(20) NOT NULL,
    branch_id bigint,
    zone_id bigint,
    address text,
    contact_person character varying(255),
    contact_phone character varying(255),
    total_bandwidth_mbps integer DEFAULT 0 NOT NULL,
    used_bandwidth_mbps integer DEFAULT 0 NOT NULL,
    uplink_provider character varying(255),
    uplink_type character varying(255),
    mikrotik_device_id bigint,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT pops_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'maintenance'::character varying])::text[])))
);


--
-- Name: pops_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pops_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pops_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pops_id_seq OWNED BY public.pops.id;


--
-- Name: port_forwards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.port_forwards (
    id bigint NOT NULL,
    client_id bigint,
    name character varying(255) NOT NULL,
    protocol character varying(255) DEFAULT 'tcp'::character varying NOT NULL,
    external_port integer NOT NULL,
    internal_ip character varying(255) NOT NULL,
    internal_port integer NOT NULL,
    wan_interface character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    mikrotik_device_id bigint,
    mikrotik_rule_id character varying(255),
    notes text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: port_forwards_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.port_forwards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: port_forwards_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.port_forwards_id_seq OWNED BY public.port_forwards.id;


--
-- Name: product_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: product_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_categories_id_seq OWNED BY public.product_categories.id;


--
-- Name: product_serials; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_serials (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    serial_number character varying(255) NOT NULL,
    mac_address character varying(255),
    status character varying(255) DEFAULT 'in_stock'::character varying NOT NULL,
    client_id bigint,
    purchase_id bigint,
    warranty_start date,
    warranty_end date,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    barcode character varying(255),
    CONSTRAINT product_serials_status_check CHECK (((status)::text = ANY ((ARRAY['in_stock'::character varying, 'sold'::character varying, 'assigned'::character varying, 'damaged'::character varying, 'returned'::character varying])::text[])))
);


--
-- Name: product_serials_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_serials_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_serials_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_serials_id_seq OWNED BY public.product_serials.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    category_id bigint,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    unit character varying(255) DEFAULT 'pcs'::character varying NOT NULL,
    purchase_price numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    selling_price numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    stock_quantity integer DEFAULT 0 NOT NULL,
    alert_quantity integer DEFAULT 10 NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    has_serial boolean DEFAULT false NOT NULL,
    warranty_months integer DEFAULT 0 NOT NULL,
    type character varying(255) DEFAULT 'instrument'::character varying NOT NULL,
    brand character varying(255),
    model character varying(255),
    barcode_prefix character varying(255),
    company_id bigint
);


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: promo_codes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.promo_codes (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    description character varying(255),
    discount_type character varying(255) DEFAULT 'fixed'::character varying NOT NULL,
    discount_value numeric(12,2) NOT NULL,
    max_discount numeric(12,2),
    usage_limit integer,
    used_count integer DEFAULT 0 NOT NULL,
    valid_from date NOT NULL,
    valid_until date NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT promo_codes_discount_type_check CHECK (((discount_type)::text = ANY ((ARRAY['fixed'::character varying, 'percent'::character varying])::text[])))
);


--
-- Name: promo_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.promo_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: promo_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.promo_codes_id_seq OWNED BY public.promo_codes.id;


--
-- Name: purchase_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.purchase_items (
    id bigint NOT NULL,
    purchase_id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer NOT NULL,
    unit_price numeric(12,2) NOT NULL,
    total numeric(12,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: purchase_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.purchase_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: purchase_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.purchase_items_id_seq OWNED BY public.purchase_items.id;


--
-- Name: purchases; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.purchases (
    id bigint NOT NULL,
    purchase_no character varying(255) NOT NULL,
    vendor_id bigint,
    purchase_date date NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    discount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(15,2) NOT NULL,
    paid numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    due numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    notes text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    invoice_number character varying(255),
    company_id bigint,
    CONSTRAINT purchases_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'received'::character varying, 'partial'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: purchases_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.purchases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: purchases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.purchases_id_seq OWNED BY public.purchases.id;


--
-- Name: radius_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.radius_settings (
    id bigint NOT NULL,
    enabled boolean DEFAULT false NOT NULL,
    server_host character varying(255) DEFAULT '127.0.0.1'::character varying NOT NULL,
    auth_port integer DEFAULT 1812 NOT NULL,
    acct_port integer DEFAULT 1813 NOT NULL,
    secret character varying(255),
    nas_identifier character varying(255),
    db_host character varying(255) DEFAULT '127.0.0.1'::character varying NOT NULL,
    db_port integer DEFAULT 3306 NOT NULL,
    db_name character varying(255) DEFAULT 'radius'::character varying NOT NULL,
    db_user character varying(255) DEFAULT 'radius'::character varying NOT NULL,
    db_password character varying(255),
    auto_sync_clients boolean DEFAULT true NOT NULL,
    auto_sync_packages boolean DEFAULT true NOT NULL,
    default_auth_type character varying(255) DEFAULT 'PAP'::character varying NOT NULL,
    extra_config json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: radius_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.radius_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: radius_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.radius_settings_id_seq OWNED BY public.radius_settings.id;


--
-- Name: real_time_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.real_time_alerts (
    id bigint NOT NULL,
    alert_type character varying(50) NOT NULL,
    severity character varying(20) DEFAULT 'info'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    message text,
    client_id bigint,
    zone_id bigint,
    device_id bigint,
    device_type character varying(30),
    metadata json,
    is_read boolean DEFAULT false NOT NULL,
    is_dismissed boolean DEFAULT false NOT NULL,
    target_role character varying(30),
    target_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: real_time_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.real_time_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: real_time_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.real_time_alerts_id_seq OWNED BY public.real_time_alerts.id;


--
-- Name: refunds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.refunds (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    payment_id bigint,
    amount numeric(12,2) NOT NULL,
    reason character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    processed_by bigint,
    processed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT refunds_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying, 'processed'::character varying])::text[])))
);


--
-- Name: refunds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.refunds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: refunds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.refunds_id_seq OWNED BY public.refunds.id;


--
-- Name: report_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.report_logs (
    id bigint NOT NULL,
    report_type character varying(255) NOT NULL,
    parameters json,
    generated_by bigint,
    file_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: report_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.report_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: report_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.report_logs_id_seq OWNED BY public.report_logs.id;


--
-- Name: request_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.request_tokens (
    id bigint NOT NULL,
    token character varying(64) NOT NULL,
    action_type character varying(50) NOT NULL,
    user_id bigint,
    used boolean DEFAULT false NOT NULL,
    used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: request_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.request_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: request_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.request_tokens_id_seq OWNED BY public.request_tokens.id;


--
-- Name: reseller_bill_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_bill_payments (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    reseller_bill_id bigint,
    amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    commission_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    payment_method character varying(255) DEFAULT 'cash'::character varying NOT NULL,
    payment_reference character varying(255),
    payment_date date NOT NULL,
    notes text,
    received_by bigint,
    company_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: reseller_bill_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_bill_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_bill_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_bill_payments_id_seq OWNED BY public.reseller_bill_payments.id;


--
-- Name: reseller_billing_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_billing_configs (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    billing_mode character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    daily_rate_per_client numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    per_minute_rate numeric(12,4) DEFAULT '0'::numeric NOT NULL,
    last_billed_date date,
    auto_deduct_wallet boolean DEFAULT true NOT NULL,
    suspend_on_zero_balance boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reseller_billing_configs_billing_mode_check CHECK (((billing_mode)::text = ANY ((ARRAY['monthly'::character varying, 'daily'::character varying, 'per_minute'::character varying])::text[])))
);


--
-- Name: reseller_billing_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_billing_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_billing_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_billing_configs_id_seq OWNED BY public.reseller_billing_configs.id;


--
-- Name: reseller_bills; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_bills (
    id bigint NOT NULL,
    bill_no character varying(255) NOT NULL,
    reseller_id bigint NOT NULL,
    bill_month integer NOT NULL,
    bill_year integer NOT NULL,
    total_recharge numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_paid numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    due_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'unpaid'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    total_clients integer DEFAULT 0 NOT NULL,
    active_clients integer DEFAULT 0 NOT NULL,
    commission_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    commission_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    opening_balance numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    closing_balance numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    company_id bigint,
    CONSTRAINT reseller_bills_status_check CHECK (((status)::text = ANY ((ARRAY['unpaid'::character varying, 'partial'::character varying, 'paid'::character varying])::text[])))
);


--
-- Name: reseller_bills_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_bills_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_bills_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_bills_id_seq OWNED BY public.reseller_bills.id;


--
-- Name: reseller_boundaries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_boundaries (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    zone_id bigint NOT NULL,
    is_exclusive boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: reseller_boundaries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_boundaries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_boundaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_boundaries_id_seq OWNED BY public.reseller_boundaries.id;


--
-- Name: reseller_client_recharges; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_client_recharges (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    client_id bigint NOT NULL,
    bill_month integer NOT NULL,
    bill_year integer NOT NULL,
    package_id bigint,
    package_name character varying(255),
    package_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    recharge_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    days integer DEFAULT 30 NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    recharged_by bigint,
    company_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    transaction_type character varying(50) DEFAULT 'recharge'::character varying NOT NULL,
    daily_rate numeric(10,4),
    remarks text,
    credited_amount numeric(12,2)
);


--
-- Name: reseller_client_recharges_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_client_recharges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_client_recharges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_client_recharges_id_seq OWNED BY public.reseller_client_recharges.id;


--
-- Name: reseller_clients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_clients (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    client_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: reseller_clients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_clients_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_clients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_clients_id_seq OWNED BY public.reseller_clients.id;


--
-- Name: reseller_commissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_commissions (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    month character varying(255) NOT NULL,
    total_clients integer DEFAULT 0 NOT NULL,
    new_clients integer DEFAULT 0 NOT NULL,
    collection_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    commission_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    commission_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    is_settled boolean DEFAULT false NOT NULL,
    settled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: reseller_commissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_commissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_commissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_commissions_id_seq OWNED BY public.reseller_commissions.id;


--
-- Name: reseller_daily_bills; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_daily_bills (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    billing_date date NOT NULL,
    active_clients_count integer DEFAULT 0 NOT NULL,
    total_minutes integer DEFAULT 0 NOT NULL,
    amount numeric(15,2) NOT NULL,
    wallet_balance_before numeric(15,2) NOT NULL,
    wallet_balance_after numeric(15,2) NOT NULL,
    status character varying(255) DEFAULT 'billed'::character varying NOT NULL,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reseller_daily_bills_status_check CHECK (((status)::text = ANY ((ARRAY['billed'::character varying, 'paid'::character varying, 'overdue'::character varying, 'waived'::character varying])::text[])))
);


--
-- Name: reseller_daily_bills_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_daily_bills_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_daily_bills_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_daily_bills_id_seq OWNED BY public.reseller_daily_bills.id;


--
-- Name: reseller_packages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_packages (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    package_id bigint NOT NULL,
    reseller_price numeric(12,2) NOT NULL,
    selling_price numeric(12,2) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: reseller_packages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_packages_id_seq OWNED BY public.reseller_packages.id;


--
-- Name: reseller_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_payments (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    type character varying(255) NOT NULL,
    payment_method_id bigint,
    payment_date date NOT NULL,
    transaction_id character varying(255),
    remarks text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT reseller_payments_type_check CHECK (((type)::text = ANY ((ARRAY['deposit'::character varying, 'withdrawal'::character varying, 'commission'::character varying, 'deduction'::character varying])::text[])))
);


--
-- Name: reseller_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_payments_id_seq OWNED BY public.reseller_payments.id;


--
-- Name: reseller_wallet_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_wallet_transactions (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    amount numeric(15,2) NOT NULL,
    balance_after numeric(15,2) NOT NULL,
    description character varying(255) NOT NULL,
    reference character varying(255),
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reseller_wallet_transactions_type_check CHECK (((type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying])::text[])))
);


--
-- Name: reseller_wallet_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_wallet_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_wallet_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_wallet_transactions_id_seq OWNED BY public.reseller_wallet_transactions.id;


--
-- Name: reseller_wallets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reseller_wallets (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    type character varying(255) NOT NULL,
    balance_after numeric(15,2) NOT NULL,
    description character varying(255) NOT NULL,
    reference_type character varying(255),
    reference_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT reseller_wallets_type_check CHECK (((type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying])::text[])))
);


--
-- Name: reseller_wallets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reseller_wallets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reseller_wallets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reseller_wallets_id_seq OWNED BY public.reseller_wallets.id;


--
-- Name: resellers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.resellers (
    id bigint NOT NULL,
    user_id bigint,
    reseller_id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    company_name character varying(255),
    email character varying(255),
    phone character varying(255) NOT NULL,
    address text,
    nid character varying(255),
    type character varying(255) DEFAULT 'bandwidth'::character varying NOT NULL,
    commission_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    wallet_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    credit_limit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    billing_type character varying(255) DEFAULT 'postpaid'::character varying NOT NULL,
    zone_id bigint,
    terminated_at timestamp(0) without time zone,
    termination_reason text,
    parent_reseller_id bigint,
    reseller_level character varying(255) DEFAULT 'reseller'::character varying NOT NULL,
    parent_commission_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    branch_id bigint,
    company_id bigint,
    raw_bandwidth character varying(255),
    youtube_bandwidth character varying(255),
    facebook_bandwidth character varying(255),
    over_due_limit numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    minimum_recharge_days integer DEFAULT 30 NOT NULL,
    thana character varying(255),
    area_name_en character varying(255),
    area_name_bn character varying(255),
    auto_recharge boolean DEFAULT false NOT NULL,
    recharge_day_type character varying(10) DEFAULT 'multi'::character varying NOT NULL,
    single_recharge_day integer,
    terminate_hour integer DEFAULT 0 NOT NULL,
    terminate_minute integer DEFAULT 5 NOT NULL,
    serial_start_from integer DEFAULT 0 NOT NULL,
    client_prefix character varying(20),
    agent_id bigint,
    ppp_short_code character varying(30),
    referral_commission_percent numeric(5,2),
    flexible_min_days integer DEFAULT 1 NOT NULL,
    bandwidth_monthly_rate numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    mikrotik_device_id bigint,
    allowed_features json,
    CONSTRAINT resellers_billing_type_check CHECK (((billing_type)::text = ANY ((ARRAY['postpaid'::character varying, 'prepaid'::character varying])::text[]))),
    CONSTRAINT resellers_reseller_level_check CHECK (((reseller_level)::text = ANY ((ARRAY['reseller'::character varying, 'sub_reseller'::character varying])::text[]))),
    CONSTRAINT resellers_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying, 'suspended'::character varying])::text[]))),
    CONSTRAINT resellers_type_check CHECK (((type)::text = ANY ((ARRAY['mac'::character varying, 'bandwidth'::character varying])::text[])))
);


--
-- Name: COLUMN resellers.bandwidth_monthly_rate; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.resellers.bandwidth_monthly_rate IS 'Monthly billing rate for bandwidth resellers (ISP charges this to reseller)';


--
-- Name: resellers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.resellers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: resellers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.resellers_id_seq OWNED BY public.resellers.id;


--
-- Name: revenue_leakage_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.revenue_leakage_alerts (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    alert_type character varying(50) NOT NULL,
    expected_amount numeric(12,2),
    actual_amount numeric(12,2),
    difference numeric(12,2),
    details text,
    is_resolved boolean DEFAULT false NOT NULL,
    resolved_at timestamp(0) without time zone,
    resolved_by bigint,
    resolution_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: revenue_leakage_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.revenue_leakage_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: revenue_leakage_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.revenue_leakage_alerts_id_seq OWNED BY public.revenue_leakage_alerts.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_permissions (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    permission_key character varying(100) NOT NULL,
    is_granted boolean DEFAULT false NOT NULL,
    granted_by bigint,
    granted_at timestamp(0) without time zone,
    revoked_by bigint,
    revoked_at timestamp(0) without time zone,
    reason text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: role_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.role_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: role_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.role_permissions_id_seq OWNED BY public.role_permissions.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: router_health_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.router_health_logs (
    id bigint NOT NULL,
    mikrotik_device_id bigint NOT NULL,
    cpu_load numeric(5,2),
    memory_usage numeric(5,2),
    temperature numeric(5,2),
    uptime_seconds integer,
    active_sessions integer,
    status character varying(20) DEFAULT 'online'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    uptime character varying(255)
);


--
-- Name: router_health_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.router_health_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: router_health_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.router_health_logs_id_seq OWNED BY public.router_health_logs.id;


--
-- Name: salaries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.salaries (
    id bigint NOT NULL,
    employee_id bigint NOT NULL,
    month integer NOT NULL,
    year integer NOT NULL,
    basic_salary numeric(12,2) NOT NULL,
    allowances numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    overtime numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    bonus numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    deductions numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    advance_deduction numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    net_salary numeric(12,2) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    payment_date date,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT salaries_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'paid'::character varying])::text[])))
);


--
-- Name: salaries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.salaries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: salaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.salaries_id_seq OWNED BY public.salaries.id;


--
-- Name: salary_payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.salary_payments (
    id bigint NOT NULL,
    salary_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    payment_date date NOT NULL,
    payment_method_id bigint,
    transaction_id character varying(255),
    remarks text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: salary_payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.salary_payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: salary_payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.salary_payments_id_seq OWNED BY public.salary_payments.id;


--
-- Name: sale_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sale_items (
    id bigint NOT NULL,
    company_id bigint,
    sale_id bigint NOT NULL,
    product_id bigint,
    product_name character varying(255) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    unit_price numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    serial_number character varying(255),
    brand character varying(255),
    model character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sale_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sale_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sale_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sale_items_id_seq OWNED BY public.sale_items.id;


--
-- Name: sales; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sales (
    id bigint NOT NULL,
    company_id bigint,
    invoice_number character varying(255),
    client_id bigint,
    customer_name character varying(255),
    customer_phone character varying(255),
    customer_type character varying(255) DEFAULT 'walk_in'::character varying NOT NULL,
    sale_date date NOT NULL,
    subtotal numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    discount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    tax_percent numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    paid numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    due numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    payment_method character varying(255),
    payment_reference character varying(255),
    notes text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sales_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sales_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sales_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sales_id_seq OWNED BY public.sales.id;


--
-- Name: scratch_card_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scratch_card_batches (
    id bigint NOT NULL,
    batch_no character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    package_id bigint,
    amount numeric(10,2) NOT NULL,
    validity_days integer DEFAULT 30 NOT NULL,
    total_cards integer NOT NULL,
    used_cards integer DEFAULT 0 NOT NULL,
    active_cards integer DEFAULT 0 NOT NULL,
    expired_cards integer DEFAULT 0 NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_by bigint,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: scratch_card_batches_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.scratch_card_batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scratch_card_batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.scratch_card_batches_id_seq OWNED BY public.scratch_card_batches.id;


--
-- Name: scratch_cards; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.scratch_cards (
    id bigint NOT NULL,
    batch_id bigint NOT NULL,
    card_number character varying(50) NOT NULL,
    pin character varying(255) NOT NULL,
    serial_number character varying(255) NOT NULL,
    amount numeric(10,2) NOT NULL,
    validity_days integer DEFAULT 30 NOT NULL,
    package_id bigint,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    used_by_client_id bigint,
    used_at timestamp(0) without time zone,
    distributor_id bigint,
    distributed_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: scratch_cards_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.scratch_cards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scratch_cards_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.scratch_cards_id_seq OWNED BY public.scratch_cards.id;


--
-- Name: security_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.security_alerts (
    id bigint NOT NULL,
    alert_type character varying(255) NOT NULL,
    user_id bigint,
    description text,
    severity character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    resolution_note text,
    resolved_by bigint,
    resolved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: security_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.security_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: security_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.security_alerts_id_seq OWNED BY public.security_alerts.id;


--
-- Name: self_service_lookup_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.self_service_lookup_logs (
    id bigint NOT NULL,
    ip_address character varying(45) NOT NULL,
    user_agent character varying(255),
    identifier_searched character varying(100) NOT NULL,
    found boolean DEFAULT false NOT NULL,
    client_id bigint,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: self_service_lookup_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.self_service_lookup_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: self_service_lookup_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.self_service_lookup_logs_id_seq OWNED BY public.self_service_lookup_logs.id;


--
-- Name: service_ratings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_ratings (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    client_id bigint NOT NULL,
    technician_id bigint,
    rating integer DEFAULT 5 NOT NULL,
    feedback text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: service_ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_ratings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_ratings_id_seq OWNED BY public.service_ratings.id;


--
-- Name: session_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.session_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    session_id character varying(255) NOT NULL,
    ip_address character varying(255) NOT NULL,
    user_agent character varying(255),
    device_type character varying(255),
    login_at timestamp(0) without time zone NOT NULL,
    logout_at timestamp(0) without time zone,
    last_activity_at timestamp(0) without time zone NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    logout_reason character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: session_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.session_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: session_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.session_logs_id_seq OWNED BY public.session_logs.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: slow_speed_alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.slow_speed_alerts (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    expected_speed numeric(10,2) NOT NULL,
    actual_speed numeric(10,2) NOT NULL,
    alert_type character varying(255) DEFAULT 'slow'::character varying NOT NULL,
    is_resolved boolean DEFAULT false NOT NULL,
    resolved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: slow_speed_alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.slow_speed_alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: slow_speed_alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.slow_speed_alerts_id_seq OWNED BY public.slow_speed_alerts.id;


--
-- Name: sms_gateways; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sms_gateways (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    api_url character varying(255),
    config json,
    is_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sms_gateways_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sms_gateways_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sms_gateways_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sms_gateways_id_seq OWNED BY public.sms_gateways.id;


--
-- Name: sms_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sms_logs (
    id bigint NOT NULL,
    phone character varying(255) NOT NULL,
    message text NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    response text,
    client_id bigint,
    sent_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event_type character varying(255),
    recipient_type character varying(255) DEFAULT 'client'::character varying NOT NULL,
    skip_reason character varying(255),
    company_id bigint,
    CONSTRAINT sms_logs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'sent'::character varying, 'failed'::character varying, 'skipped'::character varying])::text[])))
);


--
-- Name: sms_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sms_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sms_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sms_logs_id_seq OWNED BY public.sms_logs.id;


--
-- Name: sms_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sms_schedules (
    id bigint NOT NULL,
    sms_template_id bigint,
    name character varying(255) NOT NULL,
    schedule_type character varying(255) NOT NULL,
    days_before integer,
    days_after integer,
    send_time time(0) without time zone DEFAULT '10:00:00'::time without time zone NOT NULL,
    target_filter json,
    is_active boolean DEFAULT true NOT NULL,
    last_run timestamp(0) without time zone,
    next_run timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT sms_schedules_schedule_type_check CHECK (((schedule_type)::text = ANY ((ARRAY['due_reminder'::character varying, 'disconnect'::character varying, 'reconnect'::character varying, 'payment'::character varying, 'birthday'::character varying, 'custom'::character varying])::text[])))
);


--
-- Name: sms_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sms_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sms_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sms_schedules_id_seq OWNED BY public.sms_schedules.id;


--
-- Name: sms_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sms_settings (
    id bigint NOT NULL,
    provider character varying(255) DEFAULT 'none'::character varying NOT NULL,
    api_key character varying(255),
    api_secret character varying(255),
    sender_id character varying(255),
    base_url character varying(255),
    is_active boolean DEFAULT false NOT NULL,
    event_settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: sms_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sms_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sms_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sms_settings_id_seq OWNED BY public.sms_settings.id;


--
-- Name: sms_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sms_templates (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    content text NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    code character varying(255),
    company_id bigint
);


--
-- Name: sms_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sms_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sms_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sms_templates_id_seq OWNED BY public.sms_templates.id;


--
-- Name: speed_boost_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.speed_boost_requests (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    duration_hours integer DEFAULT 2 NOT NULL,
    boost_speed numeric(10,2) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    starts_at timestamp(0) without time zone,
    ends_at timestamp(0) without time zone,
    approved_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: speed_boost_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.speed_boost_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: speed_boost_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.speed_boost_requests_id_seq OWNED BY public.speed_boost_requests.id;


--
-- Name: speed_test_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.speed_test_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    download_speed numeric(10,2) NOT NULL,
    upload_speed numeric(10,2) NOT NULL,
    ping_ms integer NOT NULL,
    server_location character varying(255),
    test_type character varying(255) DEFAULT 'manual'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: speed_test_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.speed_test_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: speed_test_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.speed_test_logs_id_seq OWNED BY public.speed_test_logs.id;


--
-- Name: splitters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.splitters (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) DEFAULT '1:8'::character varying NOT NULL,
    olt_device_id bigint,
    parent_splitter_id bigint,
    port_number integer,
    location character varying(255),
    latitude numeric(10,8),
    longitude numeric(11,8),
    is_active boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: splitters_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.splitters_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: splitters_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.splitters_id_seq OWNED BY public.splitters.id;


--
-- Name: staff_dues; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_dues (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    total_collected numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total_submitted numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    current_due numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    last_collection_date date,
    last_submission_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: staff_dues_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.staff_dues_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staff_dues_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.staff_dues_id_seq OWNED BY public.staff_dues.id;


--
-- Name: staff_receipts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_receipts (
    id bigint NOT NULL,
    receipt_no character varying(255) NOT NULL,
    staff_id bigint NOT NULL,
    received_by bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    receipt_date date NOT NULL,
    payment_method character varying(255) DEFAULT 'cash'::character varying NOT NULL,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: staff_receipts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.staff_receipts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staff_receipts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.staff_receipts_id_seq OWNED BY public.staff_receipts.id;


--
-- Name: staff_submissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staff_submissions (
    id bigint NOT NULL,
    submission_no character varying(255) NOT NULL,
    staff_user_id bigint NOT NULL,
    amount numeric(12,2) NOT NULL,
    submission_date date NOT NULL,
    payment_method character varying(255) DEFAULT 'cash'::character varying NOT NULL,
    remarks text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    received_by bigint,
    received_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT staff_submissions_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'received'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: staff_submissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.staff_submissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staff_submissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.staff_submissions_id_seq OWNED BY public.staff_submissions.id;


--
-- Name: stock_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stock_logs (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    previous_quantity integer NOT NULL,
    change_quantity integer NOT NULL,
    current_quantity integer NOT NULL,
    type character varying(255) NOT NULL,
    description text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT stock_logs_type_check CHECK (((type)::text = ANY ((ARRAY['in'::character varying, 'out'::character varying, 'adjustment'::character varying])::text[])))
);


--
-- Name: stock_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_logs_id_seq OWNED BY public.stock_logs.id;


--
-- Name: stock_movements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stock_movements (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    quantity integer NOT NULL,
    stock_before integer NOT NULL,
    stock_after integer NOT NULL,
    reference character varying(255),
    description text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT stock_movements_type_check CHECK (((type)::text = ANY ((ARRAY['in'::character varying, 'out'::character varying, 'adjustment'::character varying, 'damage'::character varying, 'return'::character varying])::text[])))
);


--
-- Name: stock_movements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_movements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_movements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_movements_id_seq OWNED BY public.stock_movements.id;


--
-- Name: stocks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stocks (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    quantity integer NOT NULL,
    type character varying(255) NOT NULL,
    reference_type character varying(255),
    reference_id bigint,
    description text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT stocks_type_check CHECK (((type)::text = ANY ((ARRAY['in'::character varying, 'out'::character varying])::text[])))
);


--
-- Name: stocks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stocks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stocks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stocks_id_seq OWNED BY public.stocks.id;


--
-- Name: sub_reseller_commissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sub_reseller_commissions (
    id bigint NOT NULL,
    reseller_id bigint NOT NULL,
    sub_reseller_id bigint NOT NULL,
    client_id bigint,
    payment_id bigint,
    payment_amount numeric(15,2) NOT NULL,
    reseller_commission numeric(15,2) NOT NULL,
    sub_reseller_commission numeric(15,2) NOT NULL,
    admin_commission numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    description character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sub_reseller_commissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sub_reseller_commissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sub_reseller_commissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sub_reseller_commissions_id_seq OWNED BY public.sub_reseller_commissions.id;


--
-- Name: system_health_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_health_logs (
    id bigint NOT NULL,
    component character varying(255) NOT NULL,
    status character varying(255) NOT NULL,
    message character varying(255) NOT NULL,
    response_time_ms integer,
    details json,
    checked_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT system_health_logs_status_check CHECK (((status)::text = ANY ((ARRAY['ok'::character varying, 'warning'::character varying, 'error'::character varying])::text[])))
);


--
-- Name: system_health_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_health_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_health_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_health_logs_id_seq OWNED BY public.system_health_logs.id;


--
-- Name: system_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_permissions (
    id bigint NOT NULL,
    key character varying(100) NOT NULL,
    type character varying(20) NOT NULL,
    label character varying(255) NOT NULL,
    parent_key character varying(100),
    entity character varying(50),
    action character varying(20),
    "order" integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: system_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_permissions_id_seq OWNED BY public.system_permissions.id;


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_settings (
    id bigint NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    "group" character varying(255) DEFAULT 'general'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: system_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_settings_id_seq OWNED BY public.system_settings.id;


--
-- Name: tasks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tasks (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    due_date date,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    assigned_to bigint,
    created_by bigint,
    completed_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT tasks_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying])::text[]))),
    CONSTRAINT tasks_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: tasks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tasks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tasks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tasks_id_seq OWNED BY public.tasks.id;


--
-- Name: technician_action_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.technician_action_logs (
    id bigint NOT NULL,
    technician_id bigint NOT NULL,
    client_id bigint,
    action_type character varying(50) NOT NULL,
    action_category character varying(50) NOT NULL,
    reference_type character varying(255),
    reference_id bigint,
    action_data json NOT NULL,
    before_values json,
    after_values json,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    is_overridden boolean DEFAULT false NOT NULL,
    overridden_by bigint,
    overridden_at timestamp(0) without time zone,
    ip_address character varying(45),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: technician_action_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.technician_action_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: technician_action_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.technician_action_logs_id_seq OWNED BY public.technician_action_logs.id;


--
-- Name: technician_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.technician_assignments (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    technician_id bigint NOT NULL,
    assigned_at timestamp(0) without time zone NOT NULL,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'assigned'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT technician_assignments_status_check CHECK (((status)::text = ANY ((ARRAY['assigned'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'reassigned'::character varying])::text[])))
);


--
-- Name: technician_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.technician_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: technician_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.technician_assignments_id_seq OWNED BY public.technician_assignments.id;


--
-- Name: technician_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.technician_jobs (
    id bigint NOT NULL,
    technician_id bigint NOT NULL,
    ticket_id bigint,
    client_id bigint NOT NULL,
    job_type character varying(255) NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    description text,
    completion_notes text,
    checklist json,
    assigned_at timestamp(0) without time zone,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    response_time_minutes integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT technician_jobs_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying, 'critical'::character varying])::text[]))),
    CONSTRAINT technician_jobs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: technician_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.technician_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: technician_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.technician_jobs_id_seq OWNED BY public.technician_jobs.id;


--
-- Name: technician_shifts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.technician_shifts (
    id bigint NOT NULL,
    technician_id bigint NOT NULL,
    shift_date date NOT NULL,
    clock_in timestamp(0) without time zone,
    clock_out timestamp(0) without time zone,
    total_minutes integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: technician_shifts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.technician_shifts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: technician_shifts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.technician_shifts_id_seq OWNED BY public.technician_shifts.id;


--
-- Name: ticket_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_assignments (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    assigned_by bigint NOT NULL,
    assigned_to bigint NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_assignments_id_seq OWNED BY public.ticket_assignments.id;


--
-- Name: ticket_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    priority integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    subjects json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: ticket_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_categories_id_seq OWNED BY public.ticket_categories.id;


--
-- Name: ticket_escalations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_escalations (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    escalated_from bigint,
    escalated_to bigint,
    reason character varying(255) NOT NULL,
    escalation_level integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_escalations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_escalations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_escalations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_escalations_id_seq OWNED BY public.ticket_escalations.id;


--
-- Name: ticket_feedback; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_feedback (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    rating integer NOT NULL,
    comment text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_feedback_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_feedback_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_feedback_id_seq OWNED BY public.ticket_feedback.id;


--
-- Name: ticket_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_messages (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    user_id bigint,
    message text NOT NULL,
    attachment character varying(255),
    is_staff_reply boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: ticket_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_messages_id_seq OWNED BY public.ticket_messages.id;


--
-- Name: ticket_resolution_scores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_resolution_scores (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    response_time_score integer DEFAULT 0 NOT NULL,
    resolution_time_score integer DEFAULT 0 NOT NULL,
    customer_satisfaction_score integer DEFAULT 0 NOT NULL,
    first_contact_resolution integer DEFAULT 0 NOT NULL,
    total_score integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_resolution_scores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_resolution_scores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_resolution_scores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_resolution_scores_id_seq OWNED BY public.ticket_resolution_scores.id;


--
-- Name: ticket_sla_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_sla_configs (
    id bigint NOT NULL,
    priority character varying(255) NOT NULL,
    response_hours integer NOT NULL,
    resolution_hours integer NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_sla_configs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_sla_configs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_sla_configs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_sla_configs_id_seq OWNED BY public.ticket_sla_configs.id;


--
-- Name: ticket_sla_timers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_sla_timers (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    sla_type character varying(30) NOT NULL,
    target_minutes integer NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    paused_at timestamp(0) without time zone,
    paused_minutes integer DEFAULT 0 NOT NULL,
    completed_at timestamp(0) without time zone,
    breached boolean DEFAULT false NOT NULL,
    breached_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_sla_timers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_sla_timers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_sla_timers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_sla_timers_id_seq OWNED BY public.ticket_sla_timers.id;


--
-- Name: tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tickets (
    id bigint NOT NULL,
    ticket_no character varying(255) NOT NULL,
    client_id bigint,
    category_id bigint,
    subject character varying(255) NOT NULL,
    description text NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    assigned_to bigint,
    resolved_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    technician_id bigint,
    sla_deadline timestamp(0) without time zone,
    sla_breached boolean DEFAULT false NOT NULL,
    satisfaction_rating integer,
    satisfaction_feedback text,
    is_reopened boolean DEFAULT false NOT NULL,
    reopen_count integer DEFAULT 0 NOT NULL,
    sla_response_due_at timestamp(0) without time zone,
    sla_resolution_due_at timestamp(0) without time zone,
    sla_response_breached boolean DEFAULT false NOT NULL,
    sla_resolution_breached boolean DEFAULT false NOT NULL,
    first_response_at timestamp(0) without time zone,
    escalation_level integer DEFAULT 0 NOT NULL,
    auto_created boolean DEFAULT false NOT NULL,
    auto_category character varying(255),
    expected_fix_hours integer,
    metadata jsonb,
    company_id bigint,
    CONSTRAINT tickets_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying, 'urgent'::character varying])::text[]))),
    CONSTRAINT tickets_status_check CHECK (((status)::text = ANY ((ARRAY['open'::character varying, 'in_progress'::character varying, 'resolved'::character varying, 'closed'::character varying])::text[])))
);


--
-- Name: tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tickets_id_seq OWNED BY public.tickets.id;


--
-- Name: upazilas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.upazilas (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    district_id bigint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: upazilas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.upazilas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: upazilas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.upazilas_id_seq OWNED BY public.upazilas.id;


--
-- Name: uptime_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.uptime_logs (
    id bigint NOT NULL,
    client_id bigint NOT NULL,
    log_date date NOT NULL,
    uptime_minutes integer DEFAULT 0 NOT NULL,
    downtime_minutes integer DEFAULT 0 NOT NULL,
    uptime_percent numeric(5,2) DEFAULT '100'::numeric NOT NULL,
    downtime_periods json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: uptime_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.uptime_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: uptime_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.uptime_logs_id_seq OWNED BY public.uptime_logs.id;


--
-- Name: user_menu_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_menu_permissions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    menu_key character varying(255) NOT NULL,
    submenu_key character varying(255),
    action_key character varying(255),
    override character varying(10) NOT NULL,
    company_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_menu_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_menu_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_menu_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_menu_permissions_id_seq OWNED BY public.user_menu_permissions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    phone character varying(255),
    avatar character varying(255),
    user_type character varying(20) DEFAULT 'admin'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    performance_score integer DEFAULT 0 NOT NULL,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    password_changed_at character varying(255),
    failed_login_attempts integer DEFAULT 0 NOT NULL,
    locked_until timestamp(0) without time zone,
    last_login_ip character varying(255),
    last_login_at timestamp(0) without time zone,
    branch_id bigint,
    company_id bigint,
    CONSTRAINT users_user_type_check CHECK (((user_type)::text = ANY (ARRAY['super_admin'::text, 'admin'::text, 'employee'::text, 'client'::text, 'reseller'::text, 'technician'::text, 'sub_reseller'::text, 'manager'::text, 'card_distributor'::text, 'billman'::text])))
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vendor_ledgers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vendor_ledgers (
    id bigint NOT NULL,
    vendor_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    debit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    credit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    balance numeric(15,2) NOT NULL,
    reference character varying(255),
    description text,
    transaction_date date NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint,
    CONSTRAINT vendor_ledgers_type_check CHECK (((type)::text = ANY ((ARRAY['purchase'::character varying, 'payment'::character varying, 'return'::character varying, 'adjustment'::character varying])::text[])))
);


--
-- Name: vendor_ledgers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vendor_ledgers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vendor_ledgers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vendor_ledgers_id_seq OWNED BY public.vendor_ledgers.id;


--
-- Name: vendors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vendors (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    company character varying(255),
    email character varying(255),
    phone character varying(255) NOT NULL,
    address text,
    balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    company_id bigint,
    contact_person character varying(255),
    opening_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: vendors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vendors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vendors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vendors_id_seq OWNED BY public.vendors.id;


--
-- Name: voice_call_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.voice_call_logs (
    id bigint NOT NULL,
    phone character varying(255) NOT NULL,
    client_id bigint,
    call_type character varying(255) DEFAULT 'bill_reminder'::character varying NOT NULL,
    message_text text,
    provider character varying(255),
    provider_call_id character varying(255),
    status character varying(255) DEFAULT 'queued'::character varying NOT NULL,
    duration_seconds integer DEFAULT 0 NOT NULL,
    cost numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    provider_response json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT voice_call_logs_call_type_check CHECK (((call_type)::text = ANY ((ARRAY['bill_reminder'::character varying, 'expiry_warning'::character varying, 'payment_confirmation'::character varying, 'custom'::character varying, 'announcement'::character varying])::text[]))),
    CONSTRAINT voice_call_logs_status_check CHECK (((status)::text = ANY ((ARRAY['queued'::character varying, 'initiated'::character varying, 'ringing'::character varying, 'answered'::character varying, 'completed'::character varying, 'failed'::character varying, 'no_answer'::character varying, 'busy'::character varying])::text[])))
);


--
-- Name: voice_call_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.voice_call_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: voice_call_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.voice_call_logs_id_seq OWNED BY public.voice_call_logs.id;


--
-- Name: voice_call_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.voice_call_settings (
    id bigint NOT NULL,
    provider character varying(255) DEFAULT 'twilio'::character varying NOT NULL,
    credentials json,
    from_number character varying(255),
    is_active boolean DEFAULT false NOT NULL,
    auto_bill_reminder boolean DEFAULT false NOT NULL,
    auto_expiry_warning boolean DEFAULT false NOT NULL,
    auto_payment_confirmation boolean DEFAULT false NOT NULL,
    reminder_days_before integer DEFAULT 3 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: voice_call_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.voice_call_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: voice_call_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.voice_call_settings_id_seq OWNED BY public.voice_call_settings.id;


--
-- Name: vpn_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vpn_accounts (
    id bigint NOT NULL,
    client_id bigint,
    vpn_type character varying(255) NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    local_address character varying(255),
    remote_address character varying(255),
    dns_server character varying(255),
    profile character varying(255),
    service character varying(255) DEFAULT 'any'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    mikrotik_device_id bigint,
    mikrotik_id character varying(255),
    monthly_fee numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    name character varying(255),
    account_type character varying(255) DEFAULT 'client'::character varying NOT NULL,
    server_address character varying(255),
    company_id bigint
);


--
-- Name: vpn_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vpn_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vpn_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vpn_accounts_id_seq OWNED BY public.vpn_accounts.id;


--
-- Name: webhook_payment_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.webhook_payment_logs (
    id bigint NOT NULL,
    gateway character varying(30) NOT NULL,
    trx_id character varying(255),
    sender_number character varying(20),
    reference character varying(100),
    amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    client_id bigint,
    payment_id bigint,
    company_id bigint,
    status character varying(255) DEFAULT 'received'::character varying NOT NULL,
    failure_reason text,
    raw_payload json,
    ip_address character varying(45),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT webhook_payment_logs_status_check CHECK (((status)::text = ANY ((ARRAY['received'::character varying, 'matched'::character varying, 'applied'::character varying, 'failed'::character varying, 'duplicate'::character varying])::text[])))
);


--
-- Name: webhook_payment_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.webhook_payment_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: webhook_payment_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.webhook_payment_logs_id_seq OWNED BY public.webhook_payment_logs.id;


--
-- Name: whatsapp_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whatsapp_logs (
    id bigint NOT NULL,
    client_id bigint,
    phone character varying(255) NOT NULL,
    template_code character varying(255),
    message text NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    message_id character varying(255),
    error_message text,
    metadata json,
    cost numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: whatsapp_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.whatsapp_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: whatsapp_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.whatsapp_logs_id_seq OWNED BY public.whatsapp_logs.id;


--
-- Name: whatsapp_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whatsapp_settings (
    id bigint NOT NULL,
    provider character varying(255) DEFAULT 'whatsapp_business'::character varying NOT NULL,
    api_url character varying(255),
    api_key character varying(255),
    phone_number_id character varying(255),
    business_account_id character varying(255),
    access_token character varying(255),
    is_active boolean DEFAULT false NOT NULL,
    daily_limit integer DEFAULT 1000 NOT NULL,
    sent_today integer DEFAULT 0 NOT NULL,
    config json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: whatsapp_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.whatsapp_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: whatsapp_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.whatsapp_settings_id_seq OWNED BY public.whatsapp_settings.id;


--
-- Name: whatsapp_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whatsapp_templates (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    content text NOT NULL,
    language character varying(255) DEFAULT 'bn'::character varying NOT NULL,
    category character varying(255) DEFAULT 'utility'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    variables json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_id bigint
);


--
-- Name: whatsapp_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.whatsapp_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: whatsapp_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.whatsapp_templates_id_seq OWNED BY public.whatsapp_templates.id;


--
-- Name: zones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.zones (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    branch_id bigint,
    company_id bigint,
    upazila_id bigint,
    reseller_id bigint
);


--
-- Name: zones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.zones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: zones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.zones_id_seq OWNED BY public.zones.id;


--
-- Name: accounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts ALTER COLUMN id SET DEFAULT nextval('public.accounts_id_seq'::regclass);


--
-- Name: action_confirmations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.action_confirmations ALTER COLUMN id SET DEFAULT nextval('public.action_confirmations_id_seq'::regclass);


--
-- Name: activity_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs ALTER COLUMN id SET DEFAULT nextval('public.activity_logs_id_seq'::regclass);


--
-- Name: admin_overrides id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_overrides ALTER COLUMN id SET DEFAULT nextval('public.admin_overrides_id_seq'::regclass);


--
-- Name: advance_payment_rewards id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payment_rewards ALTER COLUMN id SET DEFAULT nextval('public.advance_payment_rewards_id_seq'::regclass);


--
-- Name: advance_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payments ALTER COLUMN id SET DEFAULT nextval('public.advance_payments_id_seq'::regclass);


--
-- Name: advance_salaries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_salaries ALTER COLUMN id SET DEFAULT nextval('public.advance_salaries_id_seq'::regclass);


--
-- Name: agent_commissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_commissions ALTER COLUMN id SET DEFAULT nextval('public.agent_commissions_id_seq'::regclass);


--
-- Name: agents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agents ALTER COLUMN id SET DEFAULT nextval('public.agents_id_seq'::regclass);


--
-- Name: announcements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcements ALTER COLUMN id SET DEFAULT nextval('public.announcements_id_seq'::regclass);


--
-- Name: api_refresh_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_refresh_tokens ALTER COLUMN id SET DEFAULT nextval('public.api_refresh_tokens_id_seq'::regclass);


--
-- Name: api_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens ALTER COLUMN id SET DEFAULT nextval('public.api_tokens_id_seq'::regclass);


--
-- Name: application_documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_documents ALTER COLUMN id SET DEFAULT nextval('public.application_documents_id_seq'::regclass);


--
-- Name: application_status_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_status_logs ALTER COLUMN id SET DEFAULT nextval('public.application_status_logs_id_seq'::regclass);


--
-- Name: area_outage_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_outage_logs ALTER COLUMN id SET DEFAULT nextval('public.area_outage_logs_id_seq'::regclass);


--
-- Name: area_profitability id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_profitability ALTER COLUMN id SET DEFAULT nextval('public.area_profitability_id_seq'::regclass);


--
-- Name: areas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.areas ALTER COLUMN id SET DEFAULT nextval('public.areas_id_seq'::regclass);


--
-- Name: attendances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attendances ALTER COLUMN id SET DEFAULT nextval('public.attendances_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: automation_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_settings ALTER COLUMN id SET DEFAULT nextval('public.automation_settings_id_seq'::regclass);


--
-- Name: backups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.backups ALTER COLUMN id SET DEFAULT nextval('public.backups_id_seq'::regclass);


--
-- Name: bandwidth_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bandwidth_logs ALTER COLUMN id SET DEFAULT nextval('public.bandwidth_logs_id_seq'::regclass);


--
-- Name: bandwidth_queues id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bandwidth_queues ALTER COLUMN id SET DEFAULT nextval('public.bandwidth_queues_id_seq'::regclass);


--
-- Name: banks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.banks ALTER COLUMN id SET DEFAULT nextval('public.banks_id_seq'::regclass);


--
-- Name: bill_adjustments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_adjustments ALTER COLUMN id SET DEFAULT nextval('public.bill_adjustments_id_seq'::regclass);


--
-- Name: bill_corrections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_corrections ALTER COLUMN id SET DEFAULT nextval('public.bill_corrections_id_seq'::regclass);


--
-- Name: bill_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_items ALTER COLUMN id SET DEFAULT nextval('public.bill_items_id_seq'::regclass);


--
-- Name: bill_splits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_splits ALTER COLUMN id SET DEFAULT nextval('public.bill_splits_id_seq'::regclass);


--
-- Name: bills id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills ALTER COLUMN id SET DEFAULT nextval('public.bills_id_seq'::regclass);


--
-- Name: boxes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.boxes ALTER COLUMN id SET DEFAULT nextval('public.boxes_id_seq'::regclass);


--
-- Name: branches id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branches ALTER COLUMN id SET DEFAULT nextval('public.branches_id_seq'::regclass);


--
-- Name: card_distributors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_distributors ALTER COLUMN id SET DEFAULT nextval('public.card_distributors_id_seq'::regclass);


--
-- Name: card_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_transactions ALTER COLUMN id SET DEFAULT nextval('public.card_transactions_id_seq'::regclass);


--
-- Name: churn_predictions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.churn_predictions ALTER COLUMN id SET DEFAULT nextval('public.churn_predictions_id_seq'::regclass);


--
-- Name: client_activity_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_activity_logs ALTER COLUMN id SET DEFAULT nextval('public.client_activity_logs_id_seq'::regclass);


--
-- Name: client_additional_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_additional_payments ALTER COLUMN id SET DEFAULT nextval('public.client_additional_payments_id_seq'::regclass);


--
-- Name: client_agreements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_agreements ALTER COLUMN id SET DEFAULT nextval('public.client_agreements_id_seq'::regclass);


--
-- Name: client_documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents ALTER COLUMN id SET DEFAULT nextval('public.client_documents_id_seq'::regclass);


--
-- Name: client_expiry_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_expiry_logs ALTER COLUMN id SET DEFAULT nextval('public.client_expiry_logs_id_seq'::regclass);


--
-- Name: client_grace_periods id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_grace_periods ALTER COLUMN id SET DEFAULT nextval('public.client_grace_periods_id_seq'::regclass);


--
-- Name: client_lifetime_values id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_lifetime_values ALTER COLUMN id SET DEFAULT nextval('public.client_lifetime_values_id_seq'::regclass);


--
-- Name: client_line_shifts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_line_shifts ALTER COLUMN id SET DEFAULT nextval('public.client_line_shifts_id_seq'::regclass);


--
-- Name: client_packages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_packages ALTER COLUMN id SET DEFAULT nextval('public.client_packages_id_seq'::regclass);


--
-- Name: client_promo_usage id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_promo_usage ALTER COLUMN id SET DEFAULT nextval('public.client_promo_usage_id_seq'::regclass);


--
-- Name: client_reconnect_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_reconnect_logs ALTER COLUMN id SET DEFAULT nextval('public.client_reconnect_logs_id_seq'::regclass);


--
-- Name: client_reconnections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_reconnections ALTER COLUMN id SET DEFAULT nextval('public.client_reconnections_id_seq'::regclass);


--
-- Name: client_recovery_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recovery_logs ALTER COLUMN id SET DEFAULT nextval('public.client_recovery_logs_id_seq'::regclass);


--
-- Name: client_schedules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_schedules ALTER COLUMN id SET DEFAULT nextval('public.client_schedules_id_seq'::regclass);


--
-- Name: client_service_charges id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_service_charges ALTER COLUMN id SET DEFAULT nextval('public.client_service_charges_id_seq'::regclass);


--
-- Name: client_session_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_session_logs ALTER COLUMN id SET DEFAULT nextval('public.client_session_logs_id_seq'::regclass);


--
-- Name: client_status_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_status_logs ALTER COLUMN id SET DEFAULT nextval('public.client_status_logs_id_seq'::regclass);


--
-- Name: client_timeline id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_timeline ALTER COLUMN id SET DEFAULT nextval('public.client_timeline_id_seq'::regclass);


--
-- Name: clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients ALTER COLUMN id SET DEFAULT nextval('public.clients_id_seq'::regclass);


--
-- Name: collection_targets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_targets ALTER COLUMN id SET DEFAULT nextval('public.collection_targets_id_seq'::regclass);


--
-- Name: collection_transfers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_transfers ALTER COLUMN id SET DEFAULT nextval('public.collection_transfers_id_seq'::regclass);


--
-- Name: collections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collections ALTER COLUMN id SET DEFAULT nextval('public.collections_id_seq'::regclass);


--
-- Name: companies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.companies ALTER COLUMN id SET DEFAULT nextval('public.companies_id_seq'::regclass);


--
-- Name: company_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_profiles ALTER COLUMN id SET DEFAULT nextval('public.company_profiles_id_seq'::regclass);


--
-- Name: correction_audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.correction_audit_logs ALTER COLUMN id SET DEFAULT nextval('public.correction_audit_logs_id_seq'::regclass);


--
-- Name: credit_debit_notes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_debit_notes ALTER COLUMN id SET DEFAULT nextval('public.credit_debit_notes_id_seq'::regclass);


--
-- Name: cron_health_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cron_health_logs ALTER COLUMN id SET DEFAULT nextval('public.cron_health_logs_id_seq'::regclass);


--
-- Name: cron_job_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cron_job_logs ALTER COLUMN id SET DEFAULT nextval('public.cron_job_logs_id_seq'::regclass);


--
-- Name: daily_cash_closes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_cash_closes ALTER COLUMN id SET DEFAULT nextval('public.daily_cash_closes_id_seq'::regclass);


--
-- Name: damaged_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_items ALTER COLUMN id SET DEFAULT nextval('public.damaged_items_id_seq'::regclass);


--
-- Name: damaged_products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_products ALTER COLUMN id SET DEFAULT nextval('public.damaged_products_id_seq'::regclass);


--
-- Name: departments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departments ALTER COLUMN id SET DEFAULT nextval('public.departments_id_seq'::regclass);


--
-- Name: designations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.designations ALTER COLUMN id SET DEFAULT nextval('public.designations_id_seq'::regclass);


--
-- Name: device_inventory id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_inventory ALTER COLUMN id SET DEFAULT nextval('public.device_inventory_id_seq'::regclass);


--
-- Name: device_operation_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_operation_logs ALTER COLUMN id SET DEFAULT nextval('public.device_operation_logs_id_seq'::regclass);


--
-- Name: device_warranties id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_warranties ALTER COLUMN id SET DEFAULT nextval('public.device_warranties_id_seq'::regclass);


--
-- Name: discounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discounts ALTER COLUMN id SET DEFAULT nextval('public.discounts_id_seq'::regclass);


--
-- Name: districts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.districts ALTER COLUMN id SET DEFAULT nextval('public.districts_id_seq'::regclass);


--
-- Name: dropdown_options id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dropdown_options ALTER COLUMN id SET DEFAULT nextval('public.dropdown_options_id_seq'::regclass);


--
-- Name: due_reminders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.due_reminders ALTER COLUMN id SET DEFAULT nextval('public.due_reminders_id_seq'::regclass);


--
-- Name: email_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates ALTER COLUMN id SET DEFAULT nextval('public.email_templates_id_seq'::regclass);


--
-- Name: employee_collections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_collections ALTER COLUMN id SET DEFAULT nextval('public.employee_collections_id_seq'::regclass);


--
-- Name: employee_performances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_performances ALTER COLUMN id SET DEFAULT nextval('public.employee_performances_id_seq'::regclass);


--
-- Name: employees id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employees ALTER COLUMN id SET DEFAULT nextval('public.employees_id_seq'::regclass);


--
-- Name: events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.events ALTER COLUMN id SET DEFAULT nextval('public.events_id_seq'::regclass);


--
-- Name: expense_heads id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expense_heads ALTER COLUMN id SET DEFAULT nextval('public.expense_heads_id_seq'::regclass);


--
-- Name: expenses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses ALTER COLUMN id SET DEFAULT nextval('public.expenses_id_seq'::regclass);


--
-- Name: export_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.export_jobs ALTER COLUMN id SET DEFAULT nextval('public.export_jobs_id_seq'::regclass);


--
-- Name: failed_job_retries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_job_retries ALTER COLUMN id SET DEFAULT nextval('public.failed_job_retries_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: family_group_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_group_members ALTER COLUMN id SET DEFAULT nextval('public.family_group_members_id_seq'::regclass);


--
-- Name: family_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_groups ALTER COLUMN id SET DEFAULT nextval('public.family_groups_id_seq'::regclass);


--
-- Name: fiber_codes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_codes ALTER COLUMN id SET DEFAULT nextval('public.fiber_codes_id_seq'::regclass);


--
-- Name: fiber_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_histories ALTER COLUMN id SET DEFAULT nextval('public.fiber_histories_id_seq'::regclass);


--
-- Name: fiber_stocks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_stocks ALTER COLUMN id SET DEFAULT nextval('public.fiber_stocks_id_seq'::regclass);


--
-- Name: fiber_usages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_usages ALTER COLUMN id SET DEFAULT nextval('public.fiber_usages_id_seq'::regclass);


--
-- Name: field_mapping_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.field_mapping_templates ALTER COLUMN id SET DEFAULT nextval('public.field_mapping_templates_id_seq'::regclass);


--
-- Name: firewall_rules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.firewall_rules ALTER COLUMN id SET DEFAULT nextval('public.firewall_rules_id_seq'::regclass);


--
-- Name: fraud_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fraud_alerts ALTER COLUMN id SET DEFAULT nextval('public.fraud_alerts_id_seq'::regclass);


--
-- Name: fund_transfers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fund_transfers ALTER COLUMN id SET DEFAULT nextval('public.fund_transfers_id_seq'::regclass);


--
-- Name: hotspot_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotspot_templates ALTER COLUMN id SET DEFAULT nextval('public.hotspot_templates_id_seq'::regclass);


--
-- Name: import_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs ALTER COLUMN id SET DEFAULT nextval('public.import_jobs_id_seq'::regclass);


--
-- Name: import_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_logs ALTER COLUMN id SET DEFAULT nextval('public.import_logs_id_seq'::regclass);


--
-- Name: income_heads id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.income_heads ALTER COLUMN id SET DEFAULT nextval('public.income_heads_id_seq'::regclass);


--
-- Name: incomes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.incomes ALTER COLUMN id SET DEFAULT nextval('public.incomes_id_seq'::regclass);


--
-- Name: instrument_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instrument_categories ALTER COLUMN id SET DEFAULT nextval('public.instrument_categories_id_seq'::regclass);


--
-- Name: instrument_out_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instrument_out_logs ALTER COLUMN id SET DEFAULT nextval('public.instrument_out_logs_id_seq'::regclass);


--
-- Name: instruments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instruments ALTER COLUMN id SET DEFAULT nextval('public.instruments_id_seq'::regclass);


--
-- Name: integrity_check_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.integrity_check_logs ALTER COLUMN id SET DEFAULT nextval('public.integrity_check_logs_id_seq'::regclass);


--
-- Name: internet_quality_scores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.internet_quality_scores ALTER COLUMN id SET DEFAULT nextval('public.internet_quality_scores_id_seq'::regclass);


--
-- Name: inventory_vendors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_vendors ALTER COLUMN id SET DEFAULT nextval('public.inventory_vendors_id_seq'::regclass);


--
-- Name: invoices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices ALTER COLUMN id SET DEFAULT nextval('public.invoices_id_seq'::regclass);


--
-- Name: ip_addresses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_addresses ALTER COLUMN id SET DEFAULT nextval('public.ip_addresses_id_seq'::regclass);


--
-- Name: ip_allocations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_allocations ALTER COLUMN id SET DEFAULT nextval('public.ip_allocations_id_seq'::regclass);


--
-- Name: ip_pools id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_pools ALTER COLUMN id SET DEFAULT nextval('public.ip_pools_id_seq'::regclass);


--
-- Name: ip_restrictions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_restrictions ALTER COLUMN id SET DEFAULT nextval('public.ip_restrictions_id_seq'::regclass);


--
-- Name: job_failure_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_failure_logs ALTER COLUMN id SET DEFAULT nextval('public.job_failure_logs_id_seq'::regclass);


--
-- Name: job_photos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_photos ALTER COLUMN id SET DEFAULT nextval('public.job_photos_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: knowledge_base_articles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles ALTER COLUMN id SET DEFAULT nextval('public.knowledge_base_articles_id_seq'::regclass);


--
-- Name: knowledge_base_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_categories ALTER COLUMN id SET DEFAULT nextval('public.knowledge_base_categories_id_seq'::regclass);


--
-- Name: leaves id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leaves ALTER COLUMN id SET DEFAULT nextval('public.leaves_id_seq'::regclass);


--
-- Name: ledger_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ledger_entries ALTER COLUMN id SET DEFAULT nextval('public.ledger_entries_id_seq'::regclass);


--
-- Name: live_pending_approvals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.live_pending_approvals ALTER COLUMN id SET DEFAULT nextval('public.live_pending_approvals_id_seq'::regclass);


--
-- Name: loan_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_payments ALTER COLUMN id SET DEFAULT nextval('public.loan_payments_id_seq'::regclass);


--
-- Name: loans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans ALTER COLUMN id SET DEFAULT nextval('public.loans_id_seq'::regclass);


--
-- Name: login_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_logs ALTER COLUMN id SET DEFAULT nextval('public.login_logs_id_seq'::regclass);


--
-- Name: mac_bindings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mac_bindings ALTER COLUMN id SET DEFAULT nextval('public.mac_bindings_id_seq'::regclass);


--
-- Name: maintenance_modes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_modes ALTER COLUMN id SET DEFAULT nextval('public.maintenance_modes_id_seq'::regclass);


--
-- Name: manager_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.manager_assignments ALTER COLUMN id SET DEFAULT nextval('public.manager_assignments_id_seq'::regclass);


--
-- Name: menu_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.menu_permissions ALTER COLUMN id SET DEFAULT nextval('public.menu_permissions_id_seq'::regclass);


--
-- Name: migration_router_snapshots id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_router_snapshots ALTER COLUMN id SET DEFAULT nextval('public.migration_router_snapshots_id_seq'::regclass);


--
-- Name: migration_runs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_runs ALTER COLUMN id SET DEFAULT nextval('public.migration_runs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: mikrotik_action_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_action_logs ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_action_logs_id_seq'::regclass);


--
-- Name: mikrotik_bandwidth_usage id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_bandwidth_usage ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_bandwidth_usage_id_seq'::regclass);


--
-- Name: mikrotik_devices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_devices ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_devices_id_seq'::regclass);


--
-- Name: mikrotik_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_profiles ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_profiles_id_seq'::regclass);


--
-- Name: mikrotik_realtime_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_realtime_sessions ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_realtime_sessions_id_seq'::regclass);


--
-- Name: mikrotik_router_stats id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_router_stats ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_router_stats_id_seq'::regclass);


--
-- Name: mikrotik_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sessions ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_sessions_id_seq'::regclass);


--
-- Name: mikrotik_simulator_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_simulator_users ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_simulator_users_id_seq'::regclass);


--
-- Name: mikrotik_sync_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sync_items ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_sync_items_id_seq'::regclass);


--
-- Name: mikrotik_sync_runs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sync_runs ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_sync_runs_id_seq'::regclass);


--
-- Name: mikrotik_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_users ALTER COLUMN id SET DEFAULT nextval('public.mikrotik_users_id_seq'::regclass);


--
-- Name: minute_packages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_packages ALTER COLUMN id SET DEFAULT nextval('public.minute_packages_id_seq'::regclass);


--
-- Name: minute_sales id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_sales ALTER COLUMN id SET DEFAULT nextval('public.minute_sales_id_seq'::regclass);


--
-- Name: month_closes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.month_closes ALTER COLUMN id SET DEFAULT nextval('public.month_closes_id_seq'::regclass);


--
-- Name: nat_rules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nat_rules ALTER COLUMN id SET DEFAULT nextval('public.nat_rules_id_seq'::regclass);


--
-- Name: network_event_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.network_event_logs ALTER COLUMN id SET DEFAULT nextval('public.network_event_logs_id_seq'::regclass);


--
-- Name: new_connection_applications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications ALTER COLUMN id SET DEFAULT nextval('public.new_connection_applications_id_seq'::regclass);


--
-- Name: nid_verifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nid_verifications ALTER COLUMN id SET DEFAULT nextval('public.nid_verifications_id_seq'::regclass);


--
-- Name: notification_queue id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_queue ALTER COLUMN id SET DEFAULT nextval('public.notification_queue_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: olt_devices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_devices ALTER COLUMN id SET DEFAULT nextval('public.olt_devices_id_seq'::regclass);


--
-- Name: olt_ports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_ports ALTER COLUMN id SET DEFAULT nextval('public.olt_ports_id_seq'::regclass);


--
-- Name: olt_snmp_stats id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_snmp_stats ALTER COLUMN id SET DEFAULT nextval('public.olt_snmp_stats_id_seq'::regclass);


--
-- Name: olt_traffic_stats id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_traffic_stats ALTER COLUMN id SET DEFAULT nextval('public.olt_traffic_stats_id_seq'::regclass);


--
-- Name: online_client_history id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.online_client_history ALTER COLUMN id SET DEFAULT nextval('public.online_client_history_id_seq'::regclass);


--
-- Name: onu_alarm_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_alarm_logs ALTER COLUMN id SET DEFAULT nextval('public.onu_alarm_logs_id_seq'::regclass);


--
-- Name: onu_alarms id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_alarms ALTER COLUMN id SET DEFAULT nextval('public.onu_alarms_id_seq'::regclass);


--
-- Name: onu_devices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_devices ALTER COLUMN id SET DEFAULT nextval('public.onu_devices_id_seq'::regclass);


--
-- Name: onu_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_events ALTER COLUMN id SET DEFAULT nextval('public.onu_events_id_seq'::regclass);


--
-- Name: onu_provision_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_provision_logs ALTER COLUMN id SET DEFAULT nextval('public.onu_provision_logs_id_seq'::regclass);


--
-- Name: onu_reboot_history id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_reboot_history ALTER COLUMN id SET DEFAULT nextval('public.onu_reboot_history_id_seq'::regclass);


--
-- Name: onus id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onus ALTER COLUMN id SET DEFAULT nextval('public.onus_id_seq'::regclass);


--
-- Name: otp_verifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.otp_verifications ALTER COLUMN id SET DEFAULT nextval('public.otp_verifications_id_seq'::regclass);


--
-- Name: package_change_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_change_requests ALTER COLUMN id SET DEFAULT nextval('public.package_change_requests_id_seq'::regclass);


--
-- Name: package_speeds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_speeds ALTER COLUMN id SET DEFAULT nextval('public.package_speeds_id_seq'::regclass);


--
-- Name: packages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.packages ALTER COLUMN id SET DEFAULT nextval('public.packages_id_seq'::regclass);


--
-- Name: password_policies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_policies ALTER COLUMN id SET DEFAULT nextval('public.password_policies_id_seq'::regclass);


--
-- Name: payment_audit_trail id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_audit_trail ALTER COLUMN id SET DEFAULT nextval('public.payment_audit_trail_id_seq'::regclass);


--
-- Name: payment_corrections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_corrections ALTER COLUMN id SET DEFAULT nextval('public.payment_corrections_id_seq'::regclass);


--
-- Name: payment_fraud_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_fraud_alerts ALTER COLUMN id SET DEFAULT nextval('public.payment_fraud_alerts_id_seq'::regclass);


--
-- Name: payment_gateway_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_gateway_transactions ALTER COLUMN id SET DEFAULT nextval('public.payment_gateway_transactions_id_seq'::regclass);


--
-- Name: payment_links id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_links ALTER COLUMN id SET DEFAULT nextval('public.payment_links_id_seq'::regclass);


--
-- Name: payment_method_perms id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_method_perms ALTER COLUMN id SET DEFAULT nextval('public.payment_method_perms_id_seq'::regclass);


--
-- Name: payment_methods id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_methods ALTER COLUMN id SET DEFAULT nextval('public.payment_methods_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: permission_audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_audit_logs ALTER COLUMN id SET DEFAULT nextval('public.permission_audit_logs_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: planned_maintenances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planned_maintenances ALTER COLUMN id SET DEFAULT nextval('public.planned_maintenances_id_seq'::regclass);


--
-- Name: pops id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pops ALTER COLUMN id SET DEFAULT nextval('public.pops_id_seq'::regclass);


--
-- Name: port_forwards id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.port_forwards ALTER COLUMN id SET DEFAULT nextval('public.port_forwards_id_seq'::regclass);


--
-- Name: product_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories ALTER COLUMN id SET DEFAULT nextval('public.product_categories_id_seq'::regclass);


--
-- Name: product_serials id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_serials ALTER COLUMN id SET DEFAULT nextval('public.product_serials_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: promo_codes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promo_codes ALTER COLUMN id SET DEFAULT nextval('public.promo_codes_id_seq'::regclass);


--
-- Name: purchase_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items ALTER COLUMN id SET DEFAULT nextval('public.purchase_items_id_seq'::regclass);


--
-- Name: purchases id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases ALTER COLUMN id SET DEFAULT nextval('public.purchases_id_seq'::regclass);


--
-- Name: radius_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.radius_settings ALTER COLUMN id SET DEFAULT nextval('public.radius_settings_id_seq'::regclass);


--
-- Name: real_time_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.real_time_alerts ALTER COLUMN id SET DEFAULT nextval('public.real_time_alerts_id_seq'::regclass);


--
-- Name: refunds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds ALTER COLUMN id SET DEFAULT nextval('public.refunds_id_seq'::regclass);


--
-- Name: report_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_logs ALTER COLUMN id SET DEFAULT nextval('public.report_logs_id_seq'::regclass);


--
-- Name: request_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_tokens ALTER COLUMN id SET DEFAULT nextval('public.request_tokens_id_seq'::regclass);


--
-- Name: reseller_bill_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bill_payments ALTER COLUMN id SET DEFAULT nextval('public.reseller_bill_payments_id_seq'::regclass);


--
-- Name: reseller_billing_configs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_billing_configs ALTER COLUMN id SET DEFAULT nextval('public.reseller_billing_configs_id_seq'::regclass);


--
-- Name: reseller_bills id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bills ALTER COLUMN id SET DEFAULT nextval('public.reseller_bills_id_seq'::regclass);


--
-- Name: reseller_boundaries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_boundaries ALTER COLUMN id SET DEFAULT nextval('public.reseller_boundaries_id_seq'::regclass);


--
-- Name: reseller_client_recharges id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_client_recharges ALTER COLUMN id SET DEFAULT nextval('public.reseller_client_recharges_id_seq'::regclass);


--
-- Name: reseller_clients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_clients ALTER COLUMN id SET DEFAULT nextval('public.reseller_clients_id_seq'::regclass);


--
-- Name: reseller_commissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_commissions ALTER COLUMN id SET DEFAULT nextval('public.reseller_commissions_id_seq'::regclass);


--
-- Name: reseller_daily_bills id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_daily_bills ALTER COLUMN id SET DEFAULT nextval('public.reseller_daily_bills_id_seq'::regclass);


--
-- Name: reseller_packages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_packages ALTER COLUMN id SET DEFAULT nextval('public.reseller_packages_id_seq'::regclass);


--
-- Name: reseller_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_payments ALTER COLUMN id SET DEFAULT nextval('public.reseller_payments_id_seq'::regclass);


--
-- Name: reseller_wallet_transactions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_wallet_transactions ALTER COLUMN id SET DEFAULT nextval('public.reseller_wallet_transactions_id_seq'::regclass);


--
-- Name: reseller_wallets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_wallets ALTER COLUMN id SET DEFAULT nextval('public.reseller_wallets_id_seq'::regclass);


--
-- Name: resellers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resellers ALTER COLUMN id SET DEFAULT nextval('public.resellers_id_seq'::regclass);


--
-- Name: revenue_leakage_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revenue_leakage_alerts ALTER COLUMN id SET DEFAULT nextval('public.revenue_leakage_alerts_id_seq'::regclass);


--
-- Name: role_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions ALTER COLUMN id SET DEFAULT nextval('public.role_permissions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: router_health_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.router_health_logs ALTER COLUMN id SET DEFAULT nextval('public.router_health_logs_id_seq'::regclass);


--
-- Name: salaries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salaries ALTER COLUMN id SET DEFAULT nextval('public.salaries_id_seq'::regclass);


--
-- Name: salary_payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salary_payments ALTER COLUMN id SET DEFAULT nextval('public.salary_payments_id_seq'::regclass);


--
-- Name: sale_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items ALTER COLUMN id SET DEFAULT nextval('public.sale_items_id_seq'::regclass);


--
-- Name: sales id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales ALTER COLUMN id SET DEFAULT nextval('public.sales_id_seq'::regclass);


--
-- Name: scratch_card_batches id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_card_batches ALTER COLUMN id SET DEFAULT nextval('public.scratch_card_batches_id_seq'::regclass);


--
-- Name: scratch_cards id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards ALTER COLUMN id SET DEFAULT nextval('public.scratch_cards_id_seq'::regclass);


--
-- Name: security_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.security_alerts ALTER COLUMN id SET DEFAULT nextval('public.security_alerts_id_seq'::regclass);


--
-- Name: self_service_lookup_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.self_service_lookup_logs ALTER COLUMN id SET DEFAULT nextval('public.self_service_lookup_logs_id_seq'::regclass);


--
-- Name: service_ratings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings ALTER COLUMN id SET DEFAULT nextval('public.service_ratings_id_seq'::regclass);


--
-- Name: session_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.session_logs ALTER COLUMN id SET DEFAULT nextval('public.session_logs_id_seq'::regclass);


--
-- Name: slow_speed_alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.slow_speed_alerts ALTER COLUMN id SET DEFAULT nextval('public.slow_speed_alerts_id_seq'::regclass);


--
-- Name: sms_gateways id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_gateways ALTER COLUMN id SET DEFAULT nextval('public.sms_gateways_id_seq'::regclass);


--
-- Name: sms_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_logs ALTER COLUMN id SET DEFAULT nextval('public.sms_logs_id_seq'::regclass);


--
-- Name: sms_schedules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_schedules ALTER COLUMN id SET DEFAULT nextval('public.sms_schedules_id_seq'::regclass);


--
-- Name: sms_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_settings ALTER COLUMN id SET DEFAULT nextval('public.sms_settings_id_seq'::regclass);


--
-- Name: sms_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_templates ALTER COLUMN id SET DEFAULT nextval('public.sms_templates_id_seq'::regclass);


--
-- Name: speed_boost_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.speed_boost_requests ALTER COLUMN id SET DEFAULT nextval('public.speed_boost_requests_id_seq'::regclass);


--
-- Name: speed_test_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.speed_test_logs ALTER COLUMN id SET DEFAULT nextval('public.speed_test_logs_id_seq'::regclass);


--
-- Name: splitters id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.splitters ALTER COLUMN id SET DEFAULT nextval('public.splitters_id_seq'::regclass);


--
-- Name: staff_dues id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_dues ALTER COLUMN id SET DEFAULT nextval('public.staff_dues_id_seq'::regclass);


--
-- Name: staff_receipts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_receipts ALTER COLUMN id SET DEFAULT nextval('public.staff_receipts_id_seq'::regclass);


--
-- Name: staff_submissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_submissions ALTER COLUMN id SET DEFAULT nextval('public.staff_submissions_id_seq'::regclass);


--
-- Name: stock_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_logs ALTER COLUMN id SET DEFAULT nextval('public.stock_logs_id_seq'::regclass);


--
-- Name: stock_movements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements ALTER COLUMN id SET DEFAULT nextval('public.stock_movements_id_seq'::regclass);


--
-- Name: stocks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks ALTER COLUMN id SET DEFAULT nextval('public.stocks_id_seq'::regclass);


--
-- Name: sub_reseller_commissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_reseller_commissions ALTER COLUMN id SET DEFAULT nextval('public.sub_reseller_commissions_id_seq'::regclass);


--
-- Name: system_health_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_health_logs ALTER COLUMN id SET DEFAULT nextval('public.system_health_logs_id_seq'::regclass);


--
-- Name: system_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_permissions ALTER COLUMN id SET DEFAULT nextval('public.system_permissions_id_seq'::regclass);


--
-- Name: system_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings ALTER COLUMN id SET DEFAULT nextval('public.system_settings_id_seq'::regclass);


--
-- Name: tasks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks ALTER COLUMN id SET DEFAULT nextval('public.tasks_id_seq'::regclass);


--
-- Name: technician_action_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_action_logs ALTER COLUMN id SET DEFAULT nextval('public.technician_action_logs_id_seq'::regclass);


--
-- Name: technician_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_assignments ALTER COLUMN id SET DEFAULT nextval('public.technician_assignments_id_seq'::regclass);


--
-- Name: technician_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_jobs ALTER COLUMN id SET DEFAULT nextval('public.technician_jobs_id_seq'::regclass);


--
-- Name: technician_shifts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_shifts ALTER COLUMN id SET DEFAULT nextval('public.technician_shifts_id_seq'::regclass);


--
-- Name: ticket_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments ALTER COLUMN id SET DEFAULT nextval('public.ticket_assignments_id_seq'::regclass);


--
-- Name: ticket_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_categories ALTER COLUMN id SET DEFAULT nextval('public.ticket_categories_id_seq'::regclass);


--
-- Name: ticket_escalations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_escalations ALTER COLUMN id SET DEFAULT nextval('public.ticket_escalations_id_seq'::regclass);


--
-- Name: ticket_feedback id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_feedback ALTER COLUMN id SET DEFAULT nextval('public.ticket_feedback_id_seq'::regclass);


--
-- Name: ticket_messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages ALTER COLUMN id SET DEFAULT nextval('public.ticket_messages_id_seq'::regclass);


--
-- Name: ticket_resolution_scores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_resolution_scores ALTER COLUMN id SET DEFAULT nextval('public.ticket_resolution_scores_id_seq'::regclass);


--
-- Name: ticket_sla_configs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_sla_configs ALTER COLUMN id SET DEFAULT nextval('public.ticket_sla_configs_id_seq'::regclass);


--
-- Name: ticket_sla_timers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_sla_timers ALTER COLUMN id SET DEFAULT nextval('public.ticket_sla_timers_id_seq'::regclass);


--
-- Name: tickets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets ALTER COLUMN id SET DEFAULT nextval('public.tickets_id_seq'::regclass);


--
-- Name: upazilas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.upazilas ALTER COLUMN id SET DEFAULT nextval('public.upazilas_id_seq'::regclass);


--
-- Name: uptime_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.uptime_logs ALTER COLUMN id SET DEFAULT nextval('public.uptime_logs_id_seq'::regclass);


--
-- Name: user_menu_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_menu_permissions ALTER COLUMN id SET DEFAULT nextval('public.user_menu_permissions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vendor_ledgers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_ledgers ALTER COLUMN id SET DEFAULT nextval('public.vendor_ledgers_id_seq'::regclass);


--
-- Name: vendors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendors ALTER COLUMN id SET DEFAULT nextval('public.vendors_id_seq'::regclass);


--
-- Name: voice_call_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voice_call_logs ALTER COLUMN id SET DEFAULT nextval('public.voice_call_logs_id_seq'::regclass);


--
-- Name: voice_call_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voice_call_settings ALTER COLUMN id SET DEFAULT nextval('public.voice_call_settings_id_seq'::regclass);


--
-- Name: vpn_accounts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vpn_accounts ALTER COLUMN id SET DEFAULT nextval('public.vpn_accounts_id_seq'::regclass);


--
-- Name: webhook_payment_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_payment_logs ALTER COLUMN id SET DEFAULT nextval('public.webhook_payment_logs_id_seq'::regclass);


--
-- Name: whatsapp_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_logs ALTER COLUMN id SET DEFAULT nextval('public.whatsapp_logs_id_seq'::regclass);


--
-- Name: whatsapp_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_settings ALTER COLUMN id SET DEFAULT nextval('public.whatsapp_settings_id_seq'::regclass);


--
-- Name: whatsapp_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_templates ALTER COLUMN id SET DEFAULT nextval('public.whatsapp_templates_id_seq'::regclass);


--
-- Name: zones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.zones ALTER COLUMN id SET DEFAULT nextval('public.zones_id_seq'::regclass);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: action_confirmations action_confirmations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.action_confirmations
    ADD CONSTRAINT action_confirmations_pkey PRIMARY KEY (id);


--
-- Name: action_confirmations action_confirmations_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.action_confirmations
    ADD CONSTRAINT action_confirmations_token_unique UNIQUE (token);


--
-- Name: activity_logs activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_pkey PRIMARY KEY (id);


--
-- Name: admin_overrides admin_overrides_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_overrides
    ADD CONSTRAINT admin_overrides_pkey PRIMARY KEY (id);


--
-- Name: advance_payment_rewards advance_payment_rewards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payment_rewards
    ADD CONSTRAINT advance_payment_rewards_pkey PRIMARY KEY (id);


--
-- Name: advance_payments advance_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payments
    ADD CONSTRAINT advance_payments_pkey PRIMARY KEY (id);


--
-- Name: advance_salaries advance_salaries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_salaries
    ADD CONSTRAINT advance_salaries_pkey PRIMARY KEY (id);


--
-- Name: agent_commissions agent_commissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_commissions
    ADD CONSTRAINT agent_commissions_pkey PRIMARY KEY (id);


--
-- Name: agents agents_agent_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_agent_id_unique UNIQUE (agent_id);


--
-- Name: agents agents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_pkey PRIMARY KEY (id);


--
-- Name: agents agents_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agents
    ADD CONSTRAINT agents_username_unique UNIQUE (username);


--
-- Name: announcements announcements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_pkey PRIMARY KEY (id);


--
-- Name: api_refresh_tokens api_refresh_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_refresh_tokens
    ADD CONSTRAINT api_refresh_tokens_pkey PRIMARY KEY (id);


--
-- Name: api_refresh_tokens api_refresh_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_refresh_tokens
    ADD CONSTRAINT api_refresh_tokens_token_unique UNIQUE (token);


--
-- Name: api_tokens api_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_pkey PRIMARY KEY (id);


--
-- Name: api_tokens api_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_token_unique UNIQUE (token);


--
-- Name: application_documents application_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_documents
    ADD CONSTRAINT application_documents_pkey PRIMARY KEY (id);


--
-- Name: application_status_logs application_status_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_status_logs
    ADD CONSTRAINT application_status_logs_pkey PRIMARY KEY (id);


--
-- Name: area_outage_logs area_outage_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_outage_logs
    ADD CONSTRAINT area_outage_logs_pkey PRIMARY KEY (id);


--
-- Name: area_profitability area_profitability_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_profitability
    ADD CONSTRAINT area_profitability_pkey PRIMARY KEY (id);


--
-- Name: area_profitability area_profitability_zone_id_month_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_profitability
    ADD CONSTRAINT area_profitability_zone_id_month_unique UNIQUE (zone_id, month);


--
-- Name: areas areas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.areas
    ADD CONSTRAINT areas_pkey PRIMARY KEY (id);


--
-- Name: attendances attendances_employee_id_attendance_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attendances
    ADD CONSTRAINT attendances_employee_id_attendance_date_unique UNIQUE (employee_id, attendance_date);


--
-- Name: attendances attendances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attendances
    ADD CONSTRAINT attendances_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: automation_settings automation_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.automation_settings
    ADD CONSTRAINT automation_settings_pkey PRIMARY KEY (id);


--
-- Name: backups backups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.backups
    ADD CONSTRAINT backups_pkey PRIMARY KEY (id);


--
-- Name: bandwidth_logs bandwidth_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bandwidth_logs
    ADD CONSTRAINT bandwidth_logs_pkey PRIMARY KEY (id);


--
-- Name: bandwidth_queues bandwidth_queues_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bandwidth_queues
    ADD CONSTRAINT bandwidth_queues_pkey PRIMARY KEY (id);


--
-- Name: banks banks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.banks
    ADD CONSTRAINT banks_pkey PRIMARY KEY (id);


--
-- Name: bill_adjustments bill_adjustments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_adjustments
    ADD CONSTRAINT bill_adjustments_pkey PRIMARY KEY (id);


--
-- Name: bill_corrections bill_corrections_correction_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_corrections
    ADD CONSTRAINT bill_corrections_correction_no_unique UNIQUE (correction_no);


--
-- Name: bill_corrections bill_corrections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_corrections
    ADD CONSTRAINT bill_corrections_pkey PRIMARY KEY (id);


--
-- Name: bill_items bill_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_items
    ADD CONSTRAINT bill_items_pkey PRIMARY KEY (id);


--
-- Name: bill_splits bill_splits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_splits
    ADD CONSTRAINT bill_splits_pkey PRIMARY KEY (id);


--
-- Name: bills bills_bill_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_bill_no_unique UNIQUE (bill_no);


--
-- Name: bills bills_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_pkey PRIMARY KEY (id);


--
-- Name: boxes boxes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.boxes
    ADD CONSTRAINT boxes_pkey PRIMARY KEY (id);


--
-- Name: branches branches_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_code_unique UNIQUE (code);


--
-- Name: branches branches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: card_distributors card_distributors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_distributors
    ADD CONSTRAINT card_distributors_pkey PRIMARY KEY (id);


--
-- Name: card_transactions card_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_transactions
    ADD CONSTRAINT card_transactions_pkey PRIMARY KEY (id);


--
-- Name: churn_predictions churn_predictions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.churn_predictions
    ADD CONSTRAINT churn_predictions_pkey PRIMARY KEY (id);


--
-- Name: client_activity_logs client_activity_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_activity_logs
    ADD CONSTRAINT client_activity_logs_pkey PRIMARY KEY (id);


--
-- Name: client_additional_payments client_additional_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_additional_payments
    ADD CONSTRAINT client_additional_payments_pkey PRIMARY KEY (id);


--
-- Name: client_agreements client_agreements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_agreements
    ADD CONSTRAINT client_agreements_pkey PRIMARY KEY (id);


--
-- Name: client_documents client_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents
    ADD CONSTRAINT client_documents_pkey PRIMARY KEY (id);


--
-- Name: client_expiry_logs client_expiry_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_expiry_logs
    ADD CONSTRAINT client_expiry_logs_pkey PRIMARY KEY (id);


--
-- Name: client_grace_periods client_grace_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_grace_periods
    ADD CONSTRAINT client_grace_periods_pkey PRIMARY KEY (id);


--
-- Name: client_lifetime_values client_lifetime_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_lifetime_values
    ADD CONSTRAINT client_lifetime_values_pkey PRIMARY KEY (id);


--
-- Name: client_line_shifts client_line_shifts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_line_shifts
    ADD CONSTRAINT client_line_shifts_pkey PRIMARY KEY (id);


--
-- Name: client_packages client_packages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_packages
    ADD CONSTRAINT client_packages_pkey PRIMARY KEY (id);


--
-- Name: client_password_reset_tokens client_password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_password_reset_tokens
    ADD CONSTRAINT client_password_reset_tokens_pkey PRIMARY KEY (phone);


--
-- Name: client_promo_usage client_promo_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_promo_usage
    ADD CONSTRAINT client_promo_usage_pkey PRIMARY KEY (id);


--
-- Name: client_reconnect_logs client_reconnect_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_reconnect_logs
    ADD CONSTRAINT client_reconnect_logs_pkey PRIMARY KEY (id);


--
-- Name: client_reconnections client_reconnections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_reconnections
    ADD CONSTRAINT client_reconnections_pkey PRIMARY KEY (id);


--
-- Name: client_recovery_logs client_recovery_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recovery_logs
    ADD CONSTRAINT client_recovery_logs_pkey PRIMARY KEY (id);


--
-- Name: client_schedules client_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_schedules
    ADD CONSTRAINT client_schedules_pkey PRIMARY KEY (id);


--
-- Name: client_service_charges client_service_charges_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_service_charges
    ADD CONSTRAINT client_service_charges_pkey PRIMARY KEY (id);


--
-- Name: client_session_logs client_session_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_session_logs
    ADD CONSTRAINT client_session_logs_pkey PRIMARY KEY (id);


--
-- Name: client_status_logs client_status_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_status_logs
    ADD CONSTRAINT client_status_logs_pkey PRIMARY KEY (id);


--
-- Name: client_timeline client_timeline_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_timeline
    ADD CONSTRAINT client_timeline_pkey PRIMARY KEY (id);


--
-- Name: clients clients_client_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_client_id_unique UNIQUE (client_id);


--
-- Name: clients clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_pkey PRIMARY KEY (id);


--
-- Name: clients clients_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_username_unique UNIQUE (username);


--
-- Name: collection_targets collection_targets_collector_id_month_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_targets
    ADD CONSTRAINT collection_targets_collector_id_month_unique UNIQUE (collector_id, month);


--
-- Name: collection_targets collection_targets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_targets
    ADD CONSTRAINT collection_targets_pkey PRIMARY KEY (id);


--
-- Name: collection_transfers collection_transfers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_transfers
    ADD CONSTRAINT collection_transfers_pkey PRIMARY KEY (id);


--
-- Name: collection_transfers collection_transfers_transfer_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_transfers
    ADD CONSTRAINT collection_transfers_transfer_no_unique UNIQUE (transfer_no);


--
-- Name: collections collections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collections
    ADD CONSTRAINT collections_pkey PRIMARY KEY (id);


--
-- Name: companies companies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_pkey PRIMARY KEY (id);


--
-- Name: companies companies_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_slug_unique UNIQUE (slug);


--
-- Name: company_profiles company_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_profiles
    ADD CONSTRAINT company_profiles_pkey PRIMARY KEY (id);


--
-- Name: correction_audit_logs correction_audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.correction_audit_logs
    ADD CONSTRAINT correction_audit_logs_pkey PRIMARY KEY (id);


--
-- Name: credit_debit_notes credit_debit_notes_note_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_debit_notes
    ADD CONSTRAINT credit_debit_notes_note_no_unique UNIQUE (note_no);


--
-- Name: credit_debit_notes credit_debit_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_debit_notes
    ADD CONSTRAINT credit_debit_notes_pkey PRIMARY KEY (id);


--
-- Name: cron_health_logs cron_health_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cron_health_logs
    ADD CONSTRAINT cron_health_logs_pkey PRIMARY KEY (id);


--
-- Name: cron_job_logs cron_job_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cron_job_logs
    ADD CONSTRAINT cron_job_logs_pkey PRIMARY KEY (id);


--
-- Name: daily_cash_closes daily_cash_closes_close_date_staff_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_cash_closes
    ADD CONSTRAINT daily_cash_closes_close_date_staff_user_id_unique UNIQUE (close_date, staff_user_id);


--
-- Name: daily_cash_closes daily_cash_closes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_cash_closes
    ADD CONSTRAINT daily_cash_closes_pkey PRIMARY KEY (id);


--
-- Name: damaged_items damaged_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_items
    ADD CONSTRAINT damaged_items_pkey PRIMARY KEY (id);


--
-- Name: damaged_products damaged_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_products
    ADD CONSTRAINT damaged_products_pkey PRIMARY KEY (id);


--
-- Name: departments departments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_pkey PRIMARY KEY (id);


--
-- Name: designations designations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.designations
    ADD CONSTRAINT designations_pkey PRIMARY KEY (id);


--
-- Name: device_inventory device_inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_inventory
    ADD CONSTRAINT device_inventory_pkey PRIMARY KEY (id);


--
-- Name: device_inventory device_inventory_serial_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_inventory
    ADD CONSTRAINT device_inventory_serial_number_unique UNIQUE (serial_number);


--
-- Name: device_operation_logs device_operation_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_operation_logs
    ADD CONSTRAINT device_operation_logs_pkey PRIMARY KEY (id);


--
-- Name: device_warranties device_warranties_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_warranties
    ADD CONSTRAINT device_warranties_pkey PRIMARY KEY (id);


--
-- Name: discounts discounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.discounts
    ADD CONSTRAINT discounts_pkey PRIMARY KEY (id);


--
-- Name: districts districts_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.districts
    ADD CONSTRAINT districts_name_unique UNIQUE (name);


--
-- Name: districts districts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.districts
    ADD CONSTRAINT districts_pkey PRIMARY KEY (id);


--
-- Name: dropdown_options dropdown_options_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dropdown_options
    ADD CONSTRAINT dropdown_options_pkey PRIMARY KEY (id);


--
-- Name: due_reminders due_reminders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.due_reminders
    ADD CONSTRAINT due_reminders_pkey PRIMARY KEY (id);


--
-- Name: email_templates email_templates_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_name_unique UNIQUE (name);


--
-- Name: email_templates email_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_pkey PRIMARY KEY (id);


--
-- Name: employee_collections employee_collections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_collections
    ADD CONSTRAINT employee_collections_pkey PRIMARY KEY (id);


--
-- Name: employee_collections employee_collections_user_id_collection_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_collections
    ADD CONSTRAINT employee_collections_user_id_collection_date_unique UNIQUE (user_id, collection_date);


--
-- Name: employee_performances employee_performances_employee_id_month_year_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_performances
    ADD CONSTRAINT employee_performances_employee_id_month_year_unique UNIQUE (employee_id, month, year);


--
-- Name: employee_performances employee_performances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_performances
    ADD CONSTRAINT employee_performances_pkey PRIMARY KEY (id);


--
-- Name: employees employees_company_employee_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_company_employee_id_unique UNIQUE (company_id, employee_id);


--
-- Name: employees employees_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_pkey PRIMARY KEY (id);


--
-- Name: events events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.events
    ADD CONSTRAINT events_pkey PRIMARY KEY (id);


--
-- Name: expense_heads expense_heads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expense_heads
    ADD CONSTRAINT expense_heads_pkey PRIMARY KEY (id);


--
-- Name: expenses expenses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_pkey PRIMARY KEY (id);


--
-- Name: export_jobs export_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.export_jobs
    ADD CONSTRAINT export_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_job_retries failed_job_retries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_job_retries
    ADD CONSTRAINT failed_job_retries_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: family_group_members family_group_members_family_group_id_client_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_group_members
    ADD CONSTRAINT family_group_members_family_group_id_client_id_unique UNIQUE (family_group_id, client_id);


--
-- Name: family_group_members family_group_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_group_members
    ADD CONSTRAINT family_group_members_pkey PRIMARY KEY (id);


--
-- Name: family_groups family_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_groups
    ADD CONSTRAINT family_groups_pkey PRIMARY KEY (id);


--
-- Name: fiber_codes fiber_codes_fiber_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_codes
    ADD CONSTRAINT fiber_codes_fiber_code_unique UNIQUE (fiber_code);


--
-- Name: fiber_codes fiber_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_codes
    ADD CONSTRAINT fiber_codes_pkey PRIMARY KEY (id);


--
-- Name: fiber_histories fiber_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_histories
    ADD CONSTRAINT fiber_histories_pkey PRIMARY KEY (id);


--
-- Name: fiber_stocks fiber_stocks_fiber_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_stocks
    ADD CONSTRAINT fiber_stocks_fiber_code_unique UNIQUE (fiber_code);


--
-- Name: fiber_stocks fiber_stocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_stocks
    ADD CONSTRAINT fiber_stocks_pkey PRIMARY KEY (id);


--
-- Name: fiber_usages fiber_usages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_usages
    ADD CONSTRAINT fiber_usages_pkey PRIMARY KEY (id);


--
-- Name: field_mapping_templates field_mapping_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.field_mapping_templates
    ADD CONSTRAINT field_mapping_templates_pkey PRIMARY KEY (id);


--
-- Name: firewall_rules firewall_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.firewall_rules
    ADD CONSTRAINT firewall_rules_pkey PRIMARY KEY (id);


--
-- Name: fraud_alerts fraud_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fraud_alerts
    ADD CONSTRAINT fraud_alerts_pkey PRIMARY KEY (id);


--
-- Name: fund_transfers fund_transfers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fund_transfers
    ADD CONSTRAINT fund_transfers_pkey PRIMARY KEY (id);


--
-- Name: hotspot_templates hotspot_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotspot_templates
    ADD CONSTRAINT hotspot_templates_pkey PRIMARY KEY (id);


--
-- Name: hotspot_templates hotspot_templates_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotspot_templates
    ADD CONSTRAINT hotspot_templates_slug_unique UNIQUE (slug);


--
-- Name: import_jobs import_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_pkey PRIMARY KEY (id);


--
-- Name: import_logs import_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_logs
    ADD CONSTRAINT import_logs_pkey PRIMARY KEY (id);


--
-- Name: income_heads income_heads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.income_heads
    ADD CONSTRAINT income_heads_pkey PRIMARY KEY (id);


--
-- Name: incomes incomes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.incomes
    ADD CONSTRAINT incomes_pkey PRIMARY KEY (id);


--
-- Name: instrument_categories instrument_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instrument_categories
    ADD CONSTRAINT instrument_categories_pkey PRIMARY KEY (id);


--
-- Name: instrument_out_logs instrument_out_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instrument_out_logs
    ADD CONSTRAINT instrument_out_logs_pkey PRIMARY KEY (id);


--
-- Name: instruments instruments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instruments
    ADD CONSTRAINT instruments_pkey PRIMARY KEY (id);


--
-- Name: integrity_check_logs integrity_check_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.integrity_check_logs
    ADD CONSTRAINT integrity_check_logs_pkey PRIMARY KEY (id);


--
-- Name: internet_quality_scores internet_quality_scores_client_id_score_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.internet_quality_scores
    ADD CONSTRAINT internet_quality_scores_client_id_score_date_unique UNIQUE (client_id, score_date);


--
-- Name: internet_quality_scores internet_quality_scores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.internet_quality_scores
    ADD CONSTRAINT internet_quality_scores_pkey PRIMARY KEY (id);


--
-- Name: inventory_vendors inventory_vendors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_vendors
    ADD CONSTRAINT inventory_vendors_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_invoice_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_invoice_no_unique UNIQUE (invoice_no);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: ip_addresses ip_addresses_address_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_addresses
    ADD CONSTRAINT ip_addresses_address_unique UNIQUE (address);


--
-- Name: ip_addresses ip_addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_addresses
    ADD CONSTRAINT ip_addresses_pkey PRIMARY KEY (id);


--
-- Name: ip_allocations ip_allocations_ip_pool_id_ip_address_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_allocations
    ADD CONSTRAINT ip_allocations_ip_pool_id_ip_address_unique UNIQUE (ip_pool_id, ip_address);


--
-- Name: ip_allocations ip_allocations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_allocations
    ADD CONSTRAINT ip_allocations_pkey PRIMARY KEY (id);


--
-- Name: ip_pools ip_pools_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_pools
    ADD CONSTRAINT ip_pools_pkey PRIMARY KEY (id);


--
-- Name: ip_restrictions ip_restrictions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_restrictions
    ADD CONSTRAINT ip_restrictions_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: job_failure_logs job_failure_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_failure_logs
    ADD CONSTRAINT job_failure_logs_pkey PRIMARY KEY (id);


--
-- Name: job_photos job_photos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_photos
    ADD CONSTRAINT job_photos_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: knowledge_base_articles knowledge_base_articles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_pkey PRIMARY KEY (id);


--
-- Name: knowledge_base_articles knowledge_base_articles_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_slug_unique UNIQUE (slug);


--
-- Name: knowledge_base_categories knowledge_base_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_categories
    ADD CONSTRAINT knowledge_base_categories_pkey PRIMARY KEY (id);


--
-- Name: knowledge_base_categories knowledge_base_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_categories
    ADD CONSTRAINT knowledge_base_categories_slug_unique UNIQUE (slug);


--
-- Name: leaves leaves_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leaves
    ADD CONSTRAINT leaves_pkey PRIMARY KEY (id);


--
-- Name: ledger_entries ledger_entries_entry_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ledger_entries
    ADD CONSTRAINT ledger_entries_entry_no_unique UNIQUE (entry_no);


--
-- Name: ledger_entries ledger_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ledger_entries
    ADD CONSTRAINT ledger_entries_pkey PRIMARY KEY (id);


--
-- Name: live_pending_approvals live_pending_approvals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.live_pending_approvals
    ADD CONSTRAINT live_pending_approvals_pkey PRIMARY KEY (id);


--
-- Name: loan_payments loan_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_payments
    ADD CONSTRAINT loan_payments_pkey PRIMARY KEY (id);


--
-- Name: loans loans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_pkey PRIMARY KEY (id);


--
-- Name: loans loans_reference_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_reference_no_unique UNIQUE (reference_no);


--
-- Name: login_logs login_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_logs
    ADD CONSTRAINT login_logs_pkey PRIMARY KEY (id);


--
-- Name: mac_bindings mac_bindings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mac_bindings
    ADD CONSTRAINT mac_bindings_pkey PRIMARY KEY (id);


--
-- Name: maintenance_modes maintenance_modes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_modes
    ADD CONSTRAINT maintenance_modes_pkey PRIMARY KEY (id);


--
-- Name: manager_assignments manager_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.manager_assignments
    ADD CONSTRAINT manager_assignments_pkey PRIMARY KEY (id);


--
-- Name: menu_permissions menu_perm_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.menu_permissions
    ADD CONSTRAINT menu_perm_unique UNIQUE (role_id, menu_key, submenu_key);


--
-- Name: menu_permissions menu_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.menu_permissions
    ADD CONSTRAINT menu_permissions_pkey PRIMARY KEY (id);


--
-- Name: migration_router_snapshots migration_router_snapshots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_router_snapshots
    ADD CONSTRAINT migration_router_snapshots_pkey PRIMARY KEY (id);


--
-- Name: migration_runs migration_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_runs
    ADD CONSTRAINT migration_runs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_action_logs mikrotik_action_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_action_logs
    ADD CONSTRAINT mikrotik_action_logs_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_bandwidth_usage mikrotik_bandwidth_usage_client_id_usage_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_bandwidth_usage
    ADD CONSTRAINT mikrotik_bandwidth_usage_client_id_usage_date_unique UNIQUE (client_id, usage_date);


--
-- Name: mikrotik_bandwidth_usage mikrotik_bandwidth_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_bandwidth_usage
    ADD CONSTRAINT mikrotik_bandwidth_usage_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_devices mikrotik_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_devices
    ADD CONSTRAINT mikrotik_devices_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_profiles mikrotik_profiles_name_company_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_profiles
    ADD CONSTRAINT mikrotik_profiles_name_company_id_unique UNIQUE (name, company_id);


--
-- Name: mikrotik_profiles mikrotik_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_profiles
    ADD CONSTRAINT mikrotik_profiles_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_realtime_sessions mikrotik_realtime_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_realtime_sessions
    ADD CONSTRAINT mikrotik_realtime_sessions_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_router_stats mikrotik_router_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_router_stats
    ADD CONSTRAINT mikrotik_router_stats_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_sessions mikrotik_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sessions
    ADD CONSTRAINT mikrotik_sessions_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_simulator_users mikrotik_simulator_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_simulator_users
    ADD CONSTRAINT mikrotik_simulator_users_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_simulator_users mikrotik_simulator_users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_simulator_users
    ADD CONSTRAINT mikrotik_simulator_users_username_unique UNIQUE (username);


--
-- Name: mikrotik_sync_items mikrotik_sync_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sync_items
    ADD CONSTRAINT mikrotik_sync_items_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_sync_runs mikrotik_sync_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sync_runs
    ADD CONSTRAINT mikrotik_sync_runs_pkey PRIMARY KEY (id);


--
-- Name: mikrotik_users mikrotik_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_users
    ADD CONSTRAINT mikrotik_users_pkey PRIMARY KEY (id);


--
-- Name: minute_packages minute_packages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_packages
    ADD CONSTRAINT minute_packages_pkey PRIMARY KEY (id);


--
-- Name: minute_sales minute_sales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_sales
    ADD CONSTRAINT minute_sales_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: month_closes month_closes_billing_month_billing_year_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.month_closes
    ADD CONSTRAINT month_closes_billing_month_billing_year_unique UNIQUE (billing_month, billing_year);


--
-- Name: month_closes month_closes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.month_closes
    ADD CONSTRAINT month_closes_pkey PRIMARY KEY (id);


--
-- Name: nat_rules nat_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nat_rules
    ADD CONSTRAINT nat_rules_pkey PRIMARY KEY (id);


--
-- Name: network_event_logs network_event_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.network_event_logs
    ADD CONSTRAINT network_event_logs_pkey PRIMARY KEY (id);


--
-- Name: new_connection_applications new_connection_applications_application_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_application_number_unique UNIQUE (application_number);


--
-- Name: new_connection_applications new_connection_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_pkey PRIMARY KEY (id);


--
-- Name: nid_verifications nid_verifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nid_verifications
    ADD CONSTRAINT nid_verifications_pkey PRIMARY KEY (id);


--
-- Name: notification_queue notification_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_queue
    ADD CONSTRAINT notification_queue_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: olt_devices olt_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_devices
    ADD CONSTRAINT olt_devices_pkey PRIMARY KEY (id);


--
-- Name: olt_ports olt_ports_olt_device_id_port_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_ports
    ADD CONSTRAINT olt_ports_olt_device_id_port_no_unique UNIQUE (olt_device_id, port_no);


--
-- Name: olt_ports olt_ports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_ports
    ADD CONSTRAINT olt_ports_pkey PRIMARY KEY (id);


--
-- Name: olt_snmp_stats olt_snmp_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_snmp_stats
    ADD CONSTRAINT olt_snmp_stats_pkey PRIMARY KEY (id);


--
-- Name: olt_traffic_stats olt_traffic_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.olt_traffic_stats
    ADD CONSTRAINT olt_traffic_stats_pkey PRIMARY KEY (id);


--
-- Name: online_client_history online_client_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.online_client_history
    ADD CONSTRAINT online_client_history_pkey PRIMARY KEY (id);


--
-- Name: onu_alarm_logs onu_alarm_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_alarm_logs
    ADD CONSTRAINT onu_alarm_logs_pkey PRIMARY KEY (id);


--
-- Name: onu_alarms onu_alarms_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_alarms
    ADD CONSTRAINT onu_alarms_pkey PRIMARY KEY (id);


--
-- Name: onu_devices onu_devices_onu_serial_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_devices
    ADD CONSTRAINT onu_devices_onu_serial_unique UNIQUE (onu_serial);


--
-- Name: onu_devices onu_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_devices
    ADD CONSTRAINT onu_devices_pkey PRIMARY KEY (id);


--
-- Name: onu_events onu_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_events
    ADD CONSTRAINT onu_events_pkey PRIMARY KEY (id);


--
-- Name: onu_provision_logs onu_provision_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_provision_logs
    ADD CONSTRAINT onu_provision_logs_pkey PRIMARY KEY (id);


--
-- Name: onu_reboot_history onu_reboot_history_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_reboot_history
    ADD CONSTRAINT onu_reboot_history_pkey PRIMARY KEY (id);


--
-- Name: onus onus_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onus
    ADD CONSTRAINT onus_pkey PRIMARY KEY (id);


--
-- Name: onus onus_serial_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onus
    ADD CONSTRAINT onus_serial_number_unique UNIQUE (serial_number);


--
-- Name: otp_verifications otp_verifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.otp_verifications
    ADD CONSTRAINT otp_verifications_pkey PRIMARY KEY (id);


--
-- Name: package_change_requests package_change_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_change_requests
    ADD CONSTRAINT package_change_requests_pkey PRIMARY KEY (id);


--
-- Name: package_speeds package_speeds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_speeds
    ADD CONSTRAINT package_speeds_pkey PRIMARY KEY (id);


--
-- Name: packages packages_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.packages
    ADD CONSTRAINT packages_code_unique UNIQUE (code);


--
-- Name: packages packages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.packages
    ADD CONSTRAINT packages_pkey PRIMARY KEY (id);


--
-- Name: password_policies password_policies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_policies
    ADD CONSTRAINT password_policies_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payment_audit_trail payment_audit_trail_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_audit_trail
    ADD CONSTRAINT payment_audit_trail_pkey PRIMARY KEY (id);


--
-- Name: payment_corrections payment_corrections_correction_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_corrections
    ADD CONSTRAINT payment_corrections_correction_no_unique UNIQUE (correction_no);


--
-- Name: payment_corrections payment_corrections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_corrections
    ADD CONSTRAINT payment_corrections_pkey PRIMARY KEY (id);


--
-- Name: payment_fraud_alerts payment_fraud_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_fraud_alerts
    ADD CONSTRAINT payment_fraud_alerts_pkey PRIMARY KEY (id);


--
-- Name: payment_gateway_transactions payment_gateway_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_gateway_transactions
    ADD CONSTRAINT payment_gateway_transactions_pkey PRIMARY KEY (id);


--
-- Name: payment_gateway_transactions payment_gateway_transactions_transaction_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_gateway_transactions
    ADD CONSTRAINT payment_gateway_transactions_transaction_id_unique UNIQUE (transaction_id);


--
-- Name: payment_links payment_links_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_links
    ADD CONSTRAINT payment_links_pkey PRIMARY KEY (id);


--
-- Name: payment_links payment_links_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_links
    ADD CONSTRAINT payment_links_token_unique UNIQUE (token);


--
-- Name: payment_method_perms payment_method_perms_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_method_perms
    ADD CONSTRAINT payment_method_perms_pkey PRIMARY KEY (id);


--
-- Name: payment_methods payment_methods_code_company_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_code_company_unique UNIQUE (code, company_id);


--
-- Name: payment_methods payment_methods_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_methods
    ADD CONSTRAINT payment_methods_pkey PRIMARY KEY (id);


--
-- Name: payments payments_payment_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_payment_no_unique UNIQUE (payment_no);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: permission_audit_logs permission_audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_audit_logs
    ADD CONSTRAINT permission_audit_logs_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: planned_maintenances planned_maintenances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planned_maintenances
    ADD CONSTRAINT planned_maintenances_pkey PRIMARY KEY (id);


--
-- Name: pops pops_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pops
    ADD CONSTRAINT pops_code_unique UNIQUE (code);


--
-- Name: pops pops_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pops
    ADD CONSTRAINT pops_pkey PRIMARY KEY (id);


--
-- Name: port_forwards port_forwards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.port_forwards
    ADD CONSTRAINT port_forwards_pkey PRIMARY KEY (id);


--
-- Name: product_categories product_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_categories
    ADD CONSTRAINT product_categories_pkey PRIMARY KEY (id);


--
-- Name: product_serials product_serials_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_serials
    ADD CONSTRAINT product_serials_pkey PRIMARY KEY (id);


--
-- Name: product_serials product_serials_serial_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_serials
    ADD CONSTRAINT product_serials_serial_number_unique UNIQUE (serial_number);


--
-- Name: products products_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_code_unique UNIQUE (code);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: promo_codes promo_codes_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promo_codes
    ADD CONSTRAINT promo_codes_code_unique UNIQUE (code);


--
-- Name: promo_codes promo_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promo_codes
    ADD CONSTRAINT promo_codes_pkey PRIMARY KEY (id);


--
-- Name: purchase_items purchase_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items
    ADD CONSTRAINT purchase_items_pkey PRIMARY KEY (id);


--
-- Name: purchases purchases_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_pkey PRIMARY KEY (id);


--
-- Name: purchases purchases_purchase_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_purchase_no_unique UNIQUE (purchase_no);


--
-- Name: radius_settings radius_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.radius_settings
    ADD CONSTRAINT radius_settings_pkey PRIMARY KEY (id);


--
-- Name: real_time_alerts real_time_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.real_time_alerts
    ADD CONSTRAINT real_time_alerts_pkey PRIMARY KEY (id);


--
-- Name: refunds refunds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_pkey PRIMARY KEY (id);


--
-- Name: report_logs report_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_logs
    ADD CONSTRAINT report_logs_pkey PRIMARY KEY (id);


--
-- Name: request_tokens request_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_tokens
    ADD CONSTRAINT request_tokens_pkey PRIMARY KEY (id);


--
-- Name: request_tokens request_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_tokens
    ADD CONSTRAINT request_tokens_token_unique UNIQUE (token);


--
-- Name: reseller_bill_payments reseller_bill_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bill_payments
    ADD CONSTRAINT reseller_bill_payments_pkey PRIMARY KEY (id);


--
-- Name: reseller_billing_configs reseller_billing_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_billing_configs
    ADD CONSTRAINT reseller_billing_configs_pkey PRIMARY KEY (id);


--
-- Name: reseller_bills reseller_bills_bill_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bills
    ADD CONSTRAINT reseller_bills_bill_no_unique UNIQUE (bill_no);


--
-- Name: reseller_bills reseller_bills_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bills
    ADD CONSTRAINT reseller_bills_pkey PRIMARY KEY (id);


--
-- Name: reseller_bills reseller_bills_reseller_id_bill_month_bill_year_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bills
    ADD CONSTRAINT reseller_bills_reseller_id_bill_month_bill_year_unique UNIQUE (reseller_id, bill_month, bill_year);


--
-- Name: reseller_boundaries reseller_boundaries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_boundaries
    ADD CONSTRAINT reseller_boundaries_pkey PRIMARY KEY (id);


--
-- Name: reseller_boundaries reseller_boundaries_reseller_id_zone_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_boundaries
    ADD CONSTRAINT reseller_boundaries_reseller_id_zone_id_unique UNIQUE (reseller_id, zone_id);


--
-- Name: reseller_client_recharges reseller_client_recharges_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_client_recharges
    ADD CONSTRAINT reseller_client_recharges_pkey PRIMARY KEY (id);


--
-- Name: reseller_clients reseller_clients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_clients
    ADD CONSTRAINT reseller_clients_pkey PRIMARY KEY (id);


--
-- Name: reseller_commissions reseller_commissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_commissions
    ADD CONSTRAINT reseller_commissions_pkey PRIMARY KEY (id);


--
-- Name: reseller_commissions reseller_commissions_reseller_id_month_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_commissions
    ADD CONSTRAINT reseller_commissions_reseller_id_month_unique UNIQUE (reseller_id, month);


--
-- Name: reseller_daily_bills reseller_daily_bills_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_daily_bills
    ADD CONSTRAINT reseller_daily_bills_pkey PRIMARY KEY (id);


--
-- Name: reseller_daily_bills reseller_daily_bills_reseller_id_billing_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_daily_bills
    ADD CONSTRAINT reseller_daily_bills_reseller_id_billing_date_unique UNIQUE (reseller_id, billing_date);


--
-- Name: reseller_packages reseller_packages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_packages
    ADD CONSTRAINT reseller_packages_pkey PRIMARY KEY (id);


--
-- Name: reseller_payments reseller_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_payments
    ADD CONSTRAINT reseller_payments_pkey PRIMARY KEY (id);


--
-- Name: reseller_wallet_transactions reseller_wallet_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_wallet_transactions
    ADD CONSTRAINT reseller_wallet_transactions_pkey PRIMARY KEY (id);


--
-- Name: reseller_wallets reseller_wallets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_wallets
    ADD CONSTRAINT reseller_wallets_pkey PRIMARY KEY (id);


--
-- Name: resellers resellers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resellers
    ADD CONSTRAINT resellers_pkey PRIMARY KEY (id);


--
-- Name: resellers resellers_reseller_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resellers
    ADD CONSTRAINT resellers_reseller_id_unique UNIQUE (reseller_id);


--
-- Name: revenue_leakage_alerts revenue_leakage_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revenue_leakage_alerts
    ADD CONSTRAINT revenue_leakage_alerts_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_role_id_permission_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_permission_key_unique UNIQUE (role_id, permission_key);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: router_health_logs router_health_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.router_health_logs
    ADD CONSTRAINT router_health_logs_pkey PRIMARY KEY (id);


--
-- Name: salaries salaries_employee_id_month_year_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salaries
    ADD CONSTRAINT salaries_employee_id_month_year_unique UNIQUE (employee_id, month, year);


--
-- Name: salaries salaries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salaries
    ADD CONSTRAINT salaries_pkey PRIMARY KEY (id);


--
-- Name: salary_payments salary_payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salary_payments
    ADD CONSTRAINT salary_payments_pkey PRIMARY KEY (id);


--
-- Name: sale_items sale_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sale_items
    ADD CONSTRAINT sale_items_pkey PRIMARY KEY (id);


--
-- Name: sales sales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sales
    ADD CONSTRAINT sales_pkey PRIMARY KEY (id);


--
-- Name: scratch_card_batches scratch_card_batches_batch_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_card_batches
    ADD CONSTRAINT scratch_card_batches_batch_no_unique UNIQUE (batch_no);


--
-- Name: scratch_card_batches scratch_card_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_card_batches
    ADD CONSTRAINT scratch_card_batches_pkey PRIMARY KEY (id);


--
-- Name: scratch_cards scratch_cards_card_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards
    ADD CONSTRAINT scratch_cards_card_number_unique UNIQUE (card_number);


--
-- Name: scratch_cards scratch_cards_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards
    ADD CONSTRAINT scratch_cards_pkey PRIMARY KEY (id);


--
-- Name: scratch_cards scratch_cards_serial_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards
    ADD CONSTRAINT scratch_cards_serial_number_unique UNIQUE (serial_number);


--
-- Name: security_alerts security_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.security_alerts
    ADD CONSTRAINT security_alerts_pkey PRIMARY KEY (id);


--
-- Name: self_service_lookup_logs self_service_lookup_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.self_service_lookup_logs
    ADD CONSTRAINT self_service_lookup_logs_pkey PRIMARY KEY (id);


--
-- Name: service_ratings service_ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_pkey PRIMARY KEY (id);


--
-- Name: session_logs session_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.session_logs
    ADD CONSTRAINT session_logs_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: slow_speed_alerts slow_speed_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.slow_speed_alerts
    ADD CONSTRAINT slow_speed_alerts_pkey PRIMARY KEY (id);


--
-- Name: sms_gateways sms_gateways_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_gateways
    ADD CONSTRAINT sms_gateways_code_unique UNIQUE (code);


--
-- Name: sms_gateways sms_gateways_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_gateways
    ADD CONSTRAINT sms_gateways_pkey PRIMARY KEY (id);


--
-- Name: sms_logs sms_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_logs
    ADD CONSTRAINT sms_logs_pkey PRIMARY KEY (id);


--
-- Name: sms_schedules sms_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_schedules
    ADD CONSTRAINT sms_schedules_pkey PRIMARY KEY (id);


--
-- Name: sms_settings sms_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_settings
    ADD CONSTRAINT sms_settings_pkey PRIMARY KEY (id);


--
-- Name: sms_templates sms_templates_code_company_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_templates
    ADD CONSTRAINT sms_templates_code_company_unique UNIQUE (code, company_id);


--
-- Name: sms_templates sms_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_templates
    ADD CONSTRAINT sms_templates_pkey PRIMARY KEY (id);


--
-- Name: speed_boost_requests speed_boost_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.speed_boost_requests
    ADD CONSTRAINT speed_boost_requests_pkey PRIMARY KEY (id);


--
-- Name: speed_test_logs speed_test_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.speed_test_logs
    ADD CONSTRAINT speed_test_logs_pkey PRIMARY KEY (id);


--
-- Name: splitters splitters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.splitters
    ADD CONSTRAINT splitters_pkey PRIMARY KEY (id);


--
-- Name: staff_dues staff_dues_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_dues
    ADD CONSTRAINT staff_dues_pkey PRIMARY KEY (id);


--
-- Name: staff_receipts staff_receipts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_receipts
    ADD CONSTRAINT staff_receipts_pkey PRIMARY KEY (id);


--
-- Name: staff_receipts staff_receipts_receipt_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_receipts
    ADD CONSTRAINT staff_receipts_receipt_no_unique UNIQUE (receipt_no);


--
-- Name: staff_submissions staff_submissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_submissions
    ADD CONSTRAINT staff_submissions_pkey PRIMARY KEY (id);


--
-- Name: staff_submissions staff_submissions_submission_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_submissions
    ADD CONSTRAINT staff_submissions_submission_no_unique UNIQUE (submission_no);


--
-- Name: stock_logs stock_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_logs
    ADD CONSTRAINT stock_logs_pkey PRIMARY KEY (id);


--
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- Name: stocks stocks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks
    ADD CONSTRAINT stocks_pkey PRIMARY KEY (id);


--
-- Name: sub_reseller_commissions sub_reseller_commissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_reseller_commissions
    ADD CONSTRAINT sub_reseller_commissions_pkey PRIMARY KEY (id);


--
-- Name: system_health_logs system_health_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_health_logs
    ADD CONSTRAINT system_health_logs_pkey PRIMARY KEY (id);


--
-- Name: system_permissions system_permissions_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_permissions
    ADD CONSTRAINT system_permissions_key_unique UNIQUE (key);


--
-- Name: system_permissions system_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_permissions
    ADD CONSTRAINT system_permissions_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_key_unique UNIQUE (key);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- Name: tasks tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_pkey PRIMARY KEY (id);


--
-- Name: technician_action_logs technician_action_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_action_logs
    ADD CONSTRAINT technician_action_logs_pkey PRIMARY KEY (id);


--
-- Name: technician_assignments technician_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_assignments
    ADD CONSTRAINT technician_assignments_pkey PRIMARY KEY (id);


--
-- Name: technician_jobs technician_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_jobs
    ADD CONSTRAINT technician_jobs_pkey PRIMARY KEY (id);


--
-- Name: technician_shifts technician_shifts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_shifts
    ADD CONSTRAINT technician_shifts_pkey PRIMARY KEY (id);


--
-- Name: technician_shifts technician_shifts_technician_id_shift_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_shifts
    ADD CONSTRAINT technician_shifts_technician_id_shift_date_unique UNIQUE (technician_id, shift_date);


--
-- Name: ticket_assignments ticket_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_pkey PRIMARY KEY (id);


--
-- Name: ticket_categories ticket_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_categories
    ADD CONSTRAINT ticket_categories_pkey PRIMARY KEY (id);


--
-- Name: ticket_escalations ticket_escalations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_escalations
    ADD CONSTRAINT ticket_escalations_pkey PRIMARY KEY (id);


--
-- Name: ticket_feedback ticket_feedback_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_feedback
    ADD CONSTRAINT ticket_feedback_pkey PRIMARY KEY (id);


--
-- Name: ticket_messages ticket_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages
    ADD CONSTRAINT ticket_messages_pkey PRIMARY KEY (id);


--
-- Name: ticket_resolution_scores ticket_resolution_scores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_resolution_scores
    ADD CONSTRAINT ticket_resolution_scores_pkey PRIMARY KEY (id);


--
-- Name: ticket_sla_configs ticket_sla_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_sla_configs
    ADD CONSTRAINT ticket_sla_configs_pkey PRIMARY KEY (id);


--
-- Name: ticket_sla_timers ticket_sla_timers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_sla_timers
    ADD CONSTRAINT ticket_sla_timers_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_ticket_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_ticket_no_unique UNIQUE (ticket_no);


--
-- Name: user_menu_permissions ump_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_menu_permissions
    ADD CONSTRAINT ump_unique UNIQUE (user_id, menu_key, submenu_key, action_key);


--
-- Name: upazilas upazilas_name_district_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.upazilas
    ADD CONSTRAINT upazilas_name_district_id_unique UNIQUE (name, district_id);


--
-- Name: upazilas upazilas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.upazilas
    ADD CONSTRAINT upazilas_pkey PRIMARY KEY (id);


--
-- Name: uptime_logs uptime_logs_client_id_log_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.uptime_logs
    ADD CONSTRAINT uptime_logs_client_id_log_date_unique UNIQUE (client_id, log_date);


--
-- Name: uptime_logs uptime_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.uptime_logs
    ADD CONSTRAINT uptime_logs_pkey PRIMARY KEY (id);


--
-- Name: user_menu_permissions user_menu_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_menu_permissions
    ADD CONSTRAINT user_menu_permissions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: vendor_ledgers vendor_ledgers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_ledgers
    ADD CONSTRAINT vendor_ledgers_pkey PRIMARY KEY (id);


--
-- Name: vendors vendors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendors
    ADD CONSTRAINT vendors_pkey PRIMARY KEY (id);


--
-- Name: voice_call_logs voice_call_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voice_call_logs
    ADD CONSTRAINT voice_call_logs_pkey PRIMARY KEY (id);


--
-- Name: voice_call_settings voice_call_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voice_call_settings
    ADD CONSTRAINT voice_call_settings_pkey PRIMARY KEY (id);


--
-- Name: vpn_accounts vpn_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vpn_accounts
    ADD CONSTRAINT vpn_accounts_pkey PRIMARY KEY (id);


--
-- Name: vpn_accounts vpn_accounts_username_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vpn_accounts
    ADD CONSTRAINT vpn_accounts_username_unique UNIQUE (username);


--
-- Name: webhook_payment_logs webhook_payment_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_payment_logs
    ADD CONSTRAINT webhook_payment_logs_pkey PRIMARY KEY (id);


--
-- Name: whatsapp_logs whatsapp_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_logs
    ADD CONSTRAINT whatsapp_logs_pkey PRIMARY KEY (id);


--
-- Name: whatsapp_settings whatsapp_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_settings
    ADD CONSTRAINT whatsapp_settings_pkey PRIMARY KEY (id);


--
-- Name: whatsapp_templates whatsapp_templates_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_templates
    ADD CONSTRAINT whatsapp_templates_code_unique UNIQUE (code);


--
-- Name: whatsapp_templates whatsapp_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_templates
    ADD CONSTRAINT whatsapp_templates_pkey PRIMARY KEY (id);


--
-- Name: zones zones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.zones
    ADD CONSTRAINT zones_pkey PRIMARY KEY (id);


--
-- Name: accounts_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_company_id_index ON public.accounts USING btree (company_id);


--
-- Name: action_confirmations_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX action_confirmations_expires_at_index ON public.action_confirmations USING btree (expires_at);


--
-- Name: action_confirmations_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX action_confirmations_user_id_status_index ON public.action_confirmations USING btree (user_id, status);


--
-- Name: activity_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_logs_company_id_index ON public.activity_logs USING btree (company_id);


--
-- Name: activity_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_logs_user_id_created_at_index ON public.activity_logs USING btree (user_id, created_at);


--
-- Name: admin_overrides_admin_id_override_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_overrides_admin_id_override_type_index ON public.admin_overrides USING btree (admin_id, override_type);


--
-- Name: admin_overrides_client_id_action_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_overrides_client_id_action_category_index ON public.admin_overrides USING btree (client_id, action_category);


--
-- Name: admin_overrides_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_overrides_created_at_index ON public.admin_overrides USING btree (created_at);


--
-- Name: admin_overrides_technician_action_log_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX admin_overrides_technician_action_log_id_index ON public.admin_overrides USING btree (technician_action_log_id);


--
-- Name: advance_payments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX advance_payments_company_id_index ON public.advance_payments USING btree (company_id);


--
-- Name: announcements_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX announcements_company_id_index ON public.announcements USING btree (company_id);


--
-- Name: api_refresh_tokens_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX api_refresh_tokens_token_index ON public.api_refresh_tokens USING btree (token);


--
-- Name: api_tokens_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX api_tokens_token_index ON public.api_tokens USING btree (token);


--
-- Name: areas_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX areas_company_id_index ON public.areas USING btree (company_id);


--
-- Name: attendances_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX attendances_company_id_index ON public.attendances USING btree (company_id);


--
-- Name: audit_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_company_id_index ON public.audit_logs USING btree (company_id);


--
-- Name: audit_logs_model_type_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_model_type_model_id_index ON public.audit_logs USING btree (model_type, model_id);


--
-- Name: audit_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_user_id_created_at_index ON public.audit_logs USING btree (user_id, created_at);


--
-- Name: automation_settings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX automation_settings_company_id_index ON public.automation_settings USING btree (company_id);


--
-- Name: bandwidth_logs_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bandwidth_logs_client_id_index ON public.bandwidth_logs USING btree (client_id);


--
-- Name: bandwidth_logs_client_id_log_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bandwidth_logs_client_id_log_date_index ON public.bandwidth_logs USING btree (client_id, log_date);


--
-- Name: bandwidth_logs_log_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bandwidth_logs_log_date_index ON public.bandwidth_logs USING btree (log_date);


--
-- Name: banks_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX banks_company_id_index ON public.banks USING btree (company_id);


--
-- Name: bill_corrections_bill_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bill_corrections_bill_id_created_at_index ON public.bill_corrections USING btree (bill_id, created_at);


--
-- Name: bill_corrections_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bill_corrections_client_id_status_index ON public.bill_corrections USING btree (client_id, status);


--
-- Name: bill_corrections_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bill_corrections_company_id_index ON public.bill_corrections USING btree (company_id);


--
-- Name: bill_items_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bill_items_company_id_index ON public.bill_items USING btree (company_id);


--
-- Name: bills_billing_month_billing_year_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bills_billing_month_billing_year_index ON public.bills USING btree (billing_month, billing_year);


--
-- Name: bills_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bills_client_id_status_index ON public.bills USING btree (client_id, status);


--
-- Name: bills_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX bills_company_id_index ON public.bills USING btree (company_id);


--
-- Name: boxes_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX boxes_company_id_index ON public.boxes USING btree (company_id);


--
-- Name: branches_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX branches_company_id_index ON public.branches USING btree (company_id);


--
-- Name: card_distributors_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX card_distributors_company_id_index ON public.card_distributors USING btree (company_id);


--
-- Name: client_activity_logs_client_id_action_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_activity_logs_client_id_action_type_index ON public.client_activity_logs USING btree (client_id, action_type);


--
-- Name: client_activity_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_activity_logs_company_id_index ON public.client_activity_logs USING btree (company_id);


--
-- Name: client_activity_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_activity_logs_created_at_index ON public.client_activity_logs USING btree (created_at);


--
-- Name: client_additional_payments_client_id_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_additional_payments_client_id_payment_date_index ON public.client_additional_payments USING btree (client_id, payment_date);


--
-- Name: client_additional_payments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_additional_payments_company_id_index ON public.client_additional_payments USING btree (company_id);


--
-- Name: client_additional_payments_payment_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_additional_payments_payment_type_index ON public.client_additional_payments USING btree (payment_type);


--
-- Name: client_additional_payments_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_additional_payments_status_index ON public.client_additional_payments USING btree (status);


--
-- Name: client_documents_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_documents_company_id_index ON public.client_documents USING btree (company_id);


--
-- Name: client_expiry_logs_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_expiry_logs_action_index ON public.client_expiry_logs USING btree (action);


--
-- Name: client_expiry_logs_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_expiry_logs_client_id_index ON public.client_expiry_logs USING btree (client_id);


--
-- Name: client_expiry_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_expiry_logs_created_at_index ON public.client_expiry_logs USING btree (created_at);


--
-- Name: client_line_shifts_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_line_shifts_company_id_index ON public.client_line_shifts USING btree (company_id);


--
-- Name: client_reconnect_logs_client_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_reconnect_logs_client_id_created_at_index ON public.client_reconnect_logs USING btree (client_id, created_at);


--
-- Name: client_reconnections_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_reconnections_company_id_index ON public.client_reconnections USING btree (company_id);


--
-- Name: client_recovery_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_recovery_logs_company_id_index ON public.client_recovery_logs USING btree (company_id);


--
-- Name: client_schedules_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_schedules_client_id_index ON public.client_schedules USING btree (client_id);


--
-- Name: client_schedules_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_schedules_company_id_index ON public.client_schedules USING btree (company_id);


--
-- Name: client_schedules_status_scheduled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_schedules_status_scheduled_at_index ON public.client_schedules USING btree (status, scheduled_at);


--
-- Name: client_session_logs_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_session_logs_client_id_index ON public.client_session_logs USING btree (client_id);


--
-- Name: client_session_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_session_logs_created_at_index ON public.client_session_logs USING btree (created_at);


--
-- Name: client_session_logs_event_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_session_logs_event_type_index ON public.client_session_logs USING btree (event_type);


--
-- Name: client_status_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_status_logs_company_id_index ON public.client_status_logs USING btree (company_id);


--
-- Name: client_timeline_client_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX client_timeline_client_id_created_at_index ON public.client_timeline USING btree (client_id, created_at);


--
-- Name: clients_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_client_id_index ON public.clients USING btree (client_id);


--
-- Name: clients_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_company_id_index ON public.clients USING btree (company_id);


--
-- Name: clients_status_zone_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_status_zone_id_index ON public.clients USING btree (status, zone_id);


--
-- Name: clients_technician_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX clients_technician_id_index ON public.clients USING btree (technician_id);


--
-- Name: collection_transfers_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX collection_transfers_company_id_index ON public.collection_transfers USING btree (company_id);


--
-- Name: collections_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX collections_company_id_index ON public.collections USING btree (company_id);


--
-- Name: company_profiles_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_profiles_company_id_index ON public.company_profiles USING btree (company_id);


--
-- Name: correction_audit_logs_auditable_type_auditable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX correction_audit_logs_auditable_type_auditable_id_index ON public.correction_audit_logs USING btree (auditable_type, auditable_id);


--
-- Name: correction_audit_logs_performed_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX correction_audit_logs_performed_by_index ON public.correction_audit_logs USING btree (performed_by);


--
-- Name: credit_debit_notes_client_id_billing_month_billing_year_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX credit_debit_notes_client_id_billing_month_billing_year_index ON public.credit_debit_notes USING btree (client_id, billing_month, billing_year);


--
-- Name: credit_debit_notes_type_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX credit_debit_notes_type_status_index ON public.credit_debit_notes USING btree (type, status);


--
-- Name: cron_health_logs_job_name_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cron_health_logs_job_name_created_at_index ON public.cron_health_logs USING btree (job_name, created_at);


--
-- Name: cron_health_logs_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cron_health_logs_status_index ON public.cron_health_logs USING btree (status);


--
-- Name: cron_job_logs_job_name_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cron_job_logs_job_name_created_at_index ON public.cron_job_logs USING btree (job_name, created_at);


--
-- Name: daily_cash_closes_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX daily_cash_closes_company_id_index ON public.daily_cash_closes USING btree (company_id);


--
-- Name: daily_cash_closes_staff_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX daily_cash_closes_staff_user_id_status_index ON public.daily_cash_closes USING btree (staff_user_id, status);


--
-- Name: departments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX departments_company_id_index ON public.departments USING btree (company_id);


--
-- Name: designations_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX designations_company_id_index ON public.designations USING btree (company_id);


--
-- Name: device_operation_logs_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX device_operation_logs_client_id_index ON public.device_operation_logs USING btree (client_id);


--
-- Name: device_operation_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX device_operation_logs_created_at_index ON public.device_operation_logs USING btree (created_at);


--
-- Name: device_operation_logs_device_type_device_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX device_operation_logs_device_type_device_id_index ON public.device_operation_logs USING btree (device_type, device_id);


--
-- Name: device_operation_logs_operation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX device_operation_logs_operation_index ON public.device_operation_logs USING btree (operation);


--
-- Name: device_warranties_device_type_device_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX device_warranties_device_type_device_id_index ON public.device_warranties USING btree (device_type, device_id);


--
-- Name: device_warranties_warranty_end_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX device_warranties_warranty_end_index ON public.device_warranties USING btree (warranty_end);


--
-- Name: discounts_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX discounts_company_id_index ON public.discounts USING btree (company_id);


--
-- Name: due_reminders_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX due_reminders_company_id_index ON public.due_reminders USING btree (company_id);


--
-- Name: employees_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX employees_company_id_index ON public.employees USING btree (company_id);


--
-- Name: expense_heads_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expense_heads_company_id_index ON public.expense_heads USING btree (company_id);


--
-- Name: expenses_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX expenses_company_id_index ON public.expenses USING btree (company_id);


--
-- Name: export_jobs_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX export_jobs_status_created_at_index ON public.export_jobs USING btree (status, created_at);


--
-- Name: failed_job_retries_status_next_retry_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_job_retries_status_next_retry_at_index ON public.failed_job_retries USING btree (status, next_retry_at);


--
-- Name: field_mapping_templates_data_type_is_default_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX field_mapping_templates_data_type_is_default_index ON public.field_mapping_templates USING btree (data_type, is_default);


--
-- Name: firewall_rules_mikrotik_device_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX firewall_rules_mikrotik_device_id_index ON public.firewall_rules USING btree (mikrotik_device_id);


--
-- Name: fraud_alerts_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fraud_alerts_status_created_at_index ON public.fraud_alerts USING btree (status, created_at);


--
-- Name: fund_transfers_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fund_transfers_company_id_index ON public.fund_transfers USING btree (company_id);


--
-- Name: idx_bills_client_month; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_bills_client_month ON public.bills USING btree (client_id, billing_month, billing_year);


--
-- Name: idx_bills_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_bills_status ON public.bills USING btree (status);


--
-- Name: idx_clients_billing_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_billing_date ON public.clients USING btree (billing_date);


--
-- Name: idx_clients_client_id_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_client_id_trgm ON public.clients USING gin (client_id public.gin_trgm_ops);


--
-- Name: idx_clients_connection_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_connection_type ON public.clients USING btree (connection_type);


--
-- Name: idx_clients_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_created_at ON public.clients USING btree (created_at DESC);


--
-- Name: idx_clients_due_amount; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_due_amount ON public.clients USING btree (due_amount);


--
-- Name: idx_clients_email_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_email_trgm ON public.clients USING gin (email public.gin_trgm_ops);


--
-- Name: idx_clients_expire_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_expire_date ON public.clients USING btree (expire_date);


--
-- Name: idx_clients_ip_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_ip_trgm ON public.clients USING gin (ip_address public.gin_trgm_ops);


--
-- Name: idx_clients_mac_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_mac_trgm ON public.clients USING gin (mac_address public.gin_trgm_ops);


--
-- Name: idx_clients_mikrotik_device_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_mikrotik_device_id ON public.clients USING btree (mikrotik_device_id);


--
-- Name: idx_clients_name_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_name_trgm ON public.clients USING gin (name public.gin_trgm_ops);


--
-- Name: idx_clients_nid_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_nid_trgm ON public.clients USING gin (nid public.gin_trgm_ops);


--
-- Name: idx_clients_package_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_package_id ON public.clients USING btree (package_id);


--
-- Name: idx_clients_phone_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_phone_trgm ON public.clients USING gin (phone public.gin_trgm_ops);


--
-- Name: idx_clients_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_status ON public.clients USING btree (status);


--
-- Name: idx_clients_username_trgm; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_clients_username_trgm ON public.clients USING gin (username public.gin_trgm_ops);


--
-- Name: idx_payments_bill_id; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_payments_bill_id ON public.payments USING btree (bill_id);


--
-- Name: idx_payments_client_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_payments_client_status ON public.payments USING btree (client_id, status);


--
-- Name: idx_payments_date; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_payments_date ON public.payments USING btree (payment_date);


--
-- Name: idx_scratch_cards_card_number; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_scratch_cards_card_number ON public.scratch_cards USING btree (card_number);


--
-- Name: idx_scratch_cards_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_scratch_cards_status ON public.scratch_cards USING btree (status);


--
-- Name: import_jobs_data_type_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_data_type_status_index ON public.import_jobs USING btree (data_type, status);


--
-- Name: import_jobs_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_status_created_at_index ON public.import_jobs USING btree (status, created_at);


--
-- Name: import_logs_import_job_id_row_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_logs_import_job_id_row_number_index ON public.import_logs USING btree (import_job_id, row_number);


--
-- Name: import_logs_import_job_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_logs_import_job_id_status_index ON public.import_logs USING btree (import_job_id, status);


--
-- Name: income_heads_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX income_heads_company_id_index ON public.income_heads USING btree (company_id);


--
-- Name: incomes_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX incomes_company_id_index ON public.incomes USING btree (company_id);


--
-- Name: instrument_categories_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instrument_categories_company_id_index ON public.instrument_categories USING btree (company_id);


--
-- Name: instruments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX instruments_company_id_index ON public.instruments USING btree (company_id);


--
-- Name: integrity_check_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX integrity_check_logs_created_at_index ON public.integrity_check_logs USING btree (created_at);


--
-- Name: integrity_check_logs_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX integrity_check_logs_status_index ON public.integrity_check_logs USING btree (status);


--
-- Name: integrity_check_logs_status_requires_review_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX integrity_check_logs_status_requires_review_index ON public.integrity_check_logs USING btree (status, requires_review);


--
-- Name: inventory_vendors_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_vendors_name_index ON public.inventory_vendors USING btree (name);


--
-- Name: inventory_vendors_phone_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_vendors_phone_index ON public.inventory_vendors USING btree (phone);


--
-- Name: ip_addresses_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_addresses_company_id_index ON public.ip_addresses USING btree (company_id);


--
-- Name: ip_addresses_ip_pool_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_addresses_ip_pool_id_index ON public.ip_addresses USING btree (ip_pool_id);


--
-- Name: ip_addresses_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_addresses_status_index ON public.ip_addresses USING btree (status);


--
-- Name: ip_allocations_ip_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_allocations_ip_address_index ON public.ip_allocations USING btree (ip_address);


--
-- Name: ip_allocations_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_allocations_status_index ON public.ip_allocations USING btree (status);


--
-- Name: ip_pools_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_pools_company_id_index ON public.ip_pools USING btree (company_id);


--
-- Name: ip_restrictions_ip_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_restrictions_ip_address_index ON public.ip_restrictions USING btree (ip_address);


--
-- Name: ip_restrictions_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ip_restrictions_type_is_active_index ON public.ip_restrictions USING btree (type, is_active);


--
-- Name: job_failure_logs_job_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX job_failure_logs_job_name_index ON public.job_failure_logs USING btree (job_name);


--
-- Name: job_failure_logs_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX job_failure_logs_status_created_at_index ON public.job_failure_logs USING btree (status, created_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: knowledge_base_articles_category_id_is_published_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX knowledge_base_articles_category_id_is_published_index ON public.knowledge_base_articles USING btree (category_id, is_published);


--
-- Name: knowledge_base_articles_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX knowledge_base_articles_company_id_index ON public.knowledge_base_articles USING btree (company_id);


--
-- Name: knowledge_base_categories_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX knowledge_base_categories_company_id_index ON public.knowledge_base_categories USING btree (company_id);


--
-- Name: leaves_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX leaves_company_id_index ON public.leaves USING btree (company_id);


--
-- Name: ledger_entries_billing_month_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ledger_entries_billing_month_index ON public.ledger_entries USING btree (billing_month);


--
-- Name: ledger_entries_client_id_entry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ledger_entries_client_id_entry_date_index ON public.ledger_entries USING btree (client_id, entry_date);


--
-- Name: ledger_entries_reference_type_reference_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ledger_entries_reference_type_reference_id_index ON public.ledger_entries USING btree (reference_type, reference_id);


--
-- Name: live_pending_approvals_status_approval_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX live_pending_approvals_status_approval_type_index ON public.live_pending_approvals USING btree (status, approval_type);


--
-- Name: login_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_logs_company_id_index ON public.login_logs USING btree (company_id);


--
-- Name: login_logs_ip_address_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_logs_ip_address_created_at_index ON public.login_logs USING btree (ip_address, created_at);


--
-- Name: login_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX login_logs_user_id_created_at_index ON public.login_logs USING btree (user_id, created_at);


--
-- Name: manager_assignments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX manager_assignments_company_id_index ON public.manager_assignments USING btree (company_id);


--
-- Name: menu_permissions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX menu_permissions_company_id_index ON public.menu_permissions USING btree (company_id);


--
-- Name: migration_router_snapshots_match_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX migration_router_snapshots_match_status_index ON public.migration_router_snapshots USING btree (match_status);


--
-- Name: mikrotik_action_logs_client_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_action_logs_client_id_created_at_index ON public.mikrotik_action_logs USING btree (client_id, created_at);


--
-- Name: mikrotik_action_logs_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_action_logs_status_created_at_index ON public.mikrotik_action_logs USING btree (status, created_at);


--
-- Name: mikrotik_action_logs_trigger_source_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_action_logs_trigger_source_created_at_index ON public.mikrotik_action_logs USING btree (trigger_source, created_at);


--
-- Name: mikrotik_devices_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_devices_company_id_index ON public.mikrotik_devices USING btree (company_id);


--
-- Name: mikrotik_realtime_sessions_mikrotik_device_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_realtime_sessions_mikrotik_device_id_is_active_index ON public.mikrotik_realtime_sessions USING btree (mikrotik_device_id, is_active);


--
-- Name: mikrotik_sessions_username_mikrotik_device_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_sessions_username_mikrotik_device_id_index ON public.mikrotik_sessions USING btree (username, mikrotik_device_id);


--
-- Name: mikrotik_sync_items_action_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_sync_items_action_status_index ON public.mikrotik_sync_items USING btree (action_status);


--
-- Name: mikrotik_sync_items_sync_run_id_diff_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_sync_items_sync_run_id_diff_type_index ON public.mikrotik_sync_items USING btree (sync_run_id, diff_type);


--
-- Name: mikrotik_sync_items_username_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_sync_items_username_index ON public.mikrotik_sync_items USING btree (username);


--
-- Name: mikrotik_sync_runs_device_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_sync_runs_device_id_created_at_index ON public.mikrotik_sync_runs USING btree (device_id, created_at);


--
-- Name: mikrotik_sync_runs_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mikrotik_sync_runs_status_index ON public.mikrotik_sync_runs USING btree (status);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: month_closes_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX month_closes_company_id_index ON public.month_closes USING btree (company_id);


--
-- Name: nat_rules_mikrotik_device_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX nat_rules_mikrotik_device_id_index ON public.nat_rules USING btree (mikrotik_device_id);


--
-- Name: network_event_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX network_event_logs_created_at_index ON public.network_event_logs USING btree (created_at);


--
-- Name: network_event_logs_event_type_is_resolved_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX network_event_logs_event_type_is_resolved_index ON public.network_event_logs USING btree (event_type, is_resolved);


--
-- Name: new_connection_applications_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX new_connection_applications_company_id_index ON public.new_connection_applications USING btree (company_id);


--
-- Name: new_connection_applications_mobile_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX new_connection_applications_mobile_number_index ON public.new_connection_applications USING btree (mobile_number);


--
-- Name: new_connection_applications_nid_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX new_connection_applications_nid_number_index ON public.new_connection_applications USING btree (nid_number);


--
-- Name: new_connection_applications_status_technician_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX new_connection_applications_status_technician_id_index ON public.new_connection_applications USING btree (status, technician_id);


--
-- Name: notification_queue_status_priority_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notification_queue_status_priority_created_at_index ON public.notification_queue USING btree (status, priority, created_at);


--
-- Name: olt_devices_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX olt_devices_company_id_index ON public.olt_devices USING btree (company_id);


--
-- Name: olt_ports_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX olt_ports_company_id_index ON public.olt_ports USING btree (company_id);


--
-- Name: olt_snmp_stats_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX olt_snmp_stats_company_id_index ON public.olt_snmp_stats USING btree (company_id);


--
-- Name: olt_snmp_stats_olt_device_id_polled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX olt_snmp_stats_olt_device_id_polled_at_index ON public.olt_snmp_stats USING btree (olt_device_id, polled_at);


--
-- Name: olt_traffic_device_port_poll; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX olt_traffic_device_port_poll ON public.olt_traffic_stats USING btree (olt_device_id, port_no, polled_at);


--
-- Name: olt_traffic_stats_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX olt_traffic_stats_company_id_index ON public.olt_traffic_stats USING btree (company_id);


--
-- Name: online_client_history_company_id_recorded_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX online_client_history_company_id_recorded_at_index ON public.online_client_history USING btree (company_id, recorded_at);


--
-- Name: onu_alarm_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_alarm_logs_created_at_index ON public.onu_alarm_logs USING btree (created_at);


--
-- Name: onu_alarm_logs_olt_device_id_alarm_type_is_resolved_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_alarm_logs_olt_device_id_alarm_type_is_resolved_index ON public.onu_alarm_logs USING btree (olt_device_id, alarm_type, is_resolved);


--
-- Name: onu_alarms_onu_device_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_alarms_onu_device_id_is_active_index ON public.onu_alarms USING btree (onu_device_id, is_active);


--
-- Name: onu_devices_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_devices_company_id_index ON public.onu_devices USING btree (company_id);


--
-- Name: onu_devices_olt_device_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_devices_olt_device_id_status_index ON public.onu_devices USING btree (olt_device_id, status);


--
-- Name: onu_events_onu_device_id_event_type_detected_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_events_onu_device_id_event_type_detected_at_index ON public.onu_events USING btree (onu_device_id, event_type, detected_at);


--
-- Name: onu_reboot_history_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_reboot_history_client_id_index ON public.onu_reboot_history USING btree (client_id);


--
-- Name: onu_reboot_history_onu_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onu_reboot_history_onu_id_created_at_index ON public.onu_reboot_history USING btree (onu_id, created_at);


--
-- Name: onus_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onus_client_id_index ON public.onus USING btree (client_id);


--
-- Name: onus_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onus_company_id_index ON public.onus USING btree (company_id);


--
-- Name: onus_olt_device_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onus_olt_device_id_index ON public.onus USING btree (olt_device_id);


--
-- Name: onus_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onus_status_index ON public.onus USING btree (status);


--
-- Name: otp_verifications_phone_purpose_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX otp_verifications_phone_purpose_index ON public.otp_verifications USING btree (phone, purpose);


--
-- Name: packages_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX packages_company_id_index ON public.packages USING btree (company_id);


--
-- Name: payment_audit_trail_payment_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_audit_trail_payment_id_created_at_index ON public.payment_audit_trail USING btree (payment_id, created_at);


--
-- Name: payment_corrections_client_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_corrections_client_id_status_index ON public.payment_corrections USING btree (client_id, status);


--
-- Name: payment_corrections_payment_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_corrections_payment_id_created_at_index ON public.payment_corrections USING btree (payment_id, created_at);


--
-- Name: payment_fraud_alerts_payment_id_alert_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_fraud_alerts_payment_id_alert_type_index ON public.payment_fraud_alerts USING btree (payment_id, alert_type);


--
-- Name: payment_gateway_transactions_gateway_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_gateway_transactions_gateway_status_index ON public.payment_gateway_transactions USING btree (gateway, status);


--
-- Name: payment_gateway_transactions_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_gateway_transactions_transaction_id_index ON public.payment_gateway_transactions USING btree (transaction_id);


--
-- Name: payment_links_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_links_status_index ON public.payment_links USING btree (status);


--
-- Name: payment_links_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_links_token_index ON public.payment_links USING btree (token);


--
-- Name: payment_method_perms_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_method_perms_company_id_index ON public.payment_method_perms USING btree (company_id);


--
-- Name: payment_method_perms_role_id_payment_method_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_method_perms_role_id_payment_method_id_index ON public.payment_method_perms USING btree (role_id, payment_method_id);


--
-- Name: payment_method_perms_user_id_payment_method_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_method_perms_user_id_payment_method_id_index ON public.payment_method_perms USING btree (user_id, payment_method_id);


--
-- Name: payment_methods_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_methods_company_id_index ON public.payment_methods USING btree (company_id);


--
-- Name: payments_client_id_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_client_id_payment_date_index ON public.payments USING btree (client_id, payment_date);


--
-- Name: payments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_company_id_index ON public.payments USING btree (company_id);


--
-- Name: permission_audit_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permission_audit_logs_created_at_index ON public.permission_audit_logs USING btree (created_at);


--
-- Name: permission_audit_logs_entity_type_entity_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permission_audit_logs_entity_type_entity_id_index ON public.permission_audit_logs USING btree (entity_type, entity_id);


--
-- Name: permission_audit_logs_user_id_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permission_audit_logs_user_id_action_index ON public.permission_audit_logs USING btree (user_id, action);


--
-- Name: pops_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pops_company_id_index ON public.pops USING btree (company_id);


--
-- Name: product_categories_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_categories_company_id_index ON public.product_categories USING btree (company_id);


--
-- Name: products_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_company_id_index ON public.products USING btree (company_id);


--
-- Name: promo_codes_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX promo_codes_company_id_index ON public.promo_codes USING btree (company_id);


--
-- Name: real_time_alerts_alert_type_is_read_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX real_time_alerts_alert_type_is_read_created_at_index ON public.real_time_alerts USING btree (alert_type, is_read, created_at);


--
-- Name: real_time_alerts_target_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX real_time_alerts_target_user_id_index ON public.real_time_alerts USING btree (target_user_id);


--
-- Name: request_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX request_tokens_expires_at_index ON public.request_tokens USING btree (expires_at);


--
-- Name: request_tokens_token_used_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX request_tokens_token_used_index ON public.request_tokens USING btree (token, used);


--
-- Name: reseller_bill_payments_reseller_id_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reseller_bill_payments_reseller_id_payment_date_index ON public.reseller_bill_payments USING btree (reseller_id, payment_date);


--
-- Name: reseller_client_recharges_client_id_bill_month_bill_year_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reseller_client_recharges_client_id_bill_month_bill_year_index ON public.reseller_client_recharges USING btree (client_id, bill_month, bill_year);


--
-- Name: reseller_client_recharges_reseller_id_bill_month_bill_year_inde; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reseller_client_recharges_reseller_id_bill_month_bill_year_inde ON public.reseller_client_recharges USING btree (reseller_id, bill_month, bill_year);


--
-- Name: reseller_commissions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reseller_commissions_company_id_index ON public.reseller_commissions USING btree (company_id);


--
-- Name: reseller_packages_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reseller_packages_company_id_index ON public.reseller_packages USING btree (company_id);


--
-- Name: reseller_payments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reseller_payments_company_id_index ON public.reseller_payments USING btree (company_id);


--
-- Name: reseller_wallets_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reseller_wallets_company_id_index ON public.reseller_wallets USING btree (company_id);


--
-- Name: resellers_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX resellers_company_id_index ON public.resellers USING btree (company_id);


--
-- Name: revenue_leakage_alerts_alert_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revenue_leakage_alerts_alert_type_index ON public.revenue_leakage_alerts USING btree (alert_type);


--
-- Name: revenue_leakage_alerts_client_id_is_resolved_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revenue_leakage_alerts_client_id_is_resolved_index ON public.revenue_leakage_alerts USING btree (client_id, is_resolved);


--
-- Name: role_permissions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_permissions_company_id_index ON public.role_permissions USING btree (company_id);


--
-- Name: role_permissions_permission_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_permissions_permission_key_index ON public.role_permissions USING btree (permission_key);


--
-- Name: role_permissions_role_id_is_granted_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_permissions_role_id_is_granted_index ON public.role_permissions USING btree (role_id, is_granted);


--
-- Name: router_health_logs_mikrotik_device_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX router_health_logs_mikrotik_device_id_created_at_index ON public.router_health_logs USING btree (mikrotik_device_id, created_at);


--
-- Name: salaries_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX salaries_company_id_index ON public.salaries USING btree (company_id);


--
-- Name: salary_payments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX salary_payments_company_id_index ON public.salary_payments USING btree (company_id);


--
-- Name: sale_items_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sale_items_product_id_index ON public.sale_items USING btree (product_id);


--
-- Name: sale_items_sale_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sale_items_sale_id_index ON public.sale_items USING btree (sale_id);


--
-- Name: sales_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sales_client_id_index ON public.sales USING btree (client_id);


--
-- Name: sales_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sales_company_id_index ON public.sales USING btree (company_id);


--
-- Name: sales_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sales_status_index ON public.sales USING btree (status);


--
-- Name: scratch_card_batches_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scratch_card_batches_company_id_index ON public.scratch_card_batches USING btree (company_id);


--
-- Name: scratch_cards_card_number_pin_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scratch_cards_card_number_pin_index ON public.scratch_cards USING btree (card_number, pin);


--
-- Name: scratch_cards_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scratch_cards_company_id_index ON public.scratch_cards USING btree (company_id);


--
-- Name: scratch_cards_serial_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scratch_cards_serial_number_index ON public.scratch_cards USING btree (serial_number);


--
-- Name: scratch_cards_status_batch_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX scratch_cards_status_batch_id_index ON public.scratch_cards USING btree (status, batch_id);


--
-- Name: security_alerts_status_severity_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX security_alerts_status_severity_created_at_index ON public.security_alerts USING btree (status, severity, created_at);


--
-- Name: self_service_lookup_logs_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX self_service_lookup_logs_client_id_index ON public.self_service_lookup_logs USING btree (client_id);


--
-- Name: self_service_lookup_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX self_service_lookup_logs_created_at_index ON public.self_service_lookup_logs USING btree (created_at);


--
-- Name: self_service_lookup_logs_ip_address_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX self_service_lookup_logs_ip_address_index ON public.self_service_lookup_logs USING btree (ip_address);


--
-- Name: session_logs_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX session_logs_session_id_index ON public.session_logs USING btree (session_id);


--
-- Name: session_logs_user_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX session_logs_user_id_is_active_index ON public.session_logs USING btree (user_id, is_active);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: sms_logs_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sms_logs_company_id_index ON public.sms_logs USING btree (company_id);


--
-- Name: sms_settings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sms_settings_company_id_index ON public.sms_settings USING btree (company_id);


--
-- Name: sms_templates_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sms_templates_company_id_index ON public.sms_templates USING btree (company_id);


--
-- Name: speed_test_logs_client_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX speed_test_logs_client_id_created_at_index ON public.speed_test_logs USING btree (client_id, created_at);


--
-- Name: splitters_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX splitters_company_id_index ON public.splitters USING btree (company_id);


--
-- Name: splitters_olt_device_id_parent_splitter_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX splitters_olt_device_id_parent_splitter_id_index ON public.splitters USING btree (olt_device_id, parent_splitter_id);


--
-- Name: staff_dues_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_dues_company_id_index ON public.staff_dues USING btree (company_id);


--
-- Name: staff_receipts_staff_id_receipt_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_receipts_staff_id_receipt_date_index ON public.staff_receipts USING btree (staff_id, receipt_date);


--
-- Name: staff_submissions_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX staff_submissions_company_id_index ON public.staff_submissions USING btree (company_id);


--
-- Name: system_health_logs_component_checked_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_health_logs_component_checked_at_index ON public.system_health_logs USING btree (component, checked_at);


--
-- Name: system_health_logs_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_health_logs_status_index ON public.system_health_logs USING btree (status);


--
-- Name: system_permissions_parent_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_permissions_parent_key_index ON public.system_permissions USING btree (parent_key);


--
-- Name: system_permissions_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_permissions_type_is_active_index ON public.system_permissions USING btree (type, is_active);


--
-- Name: system_settings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_settings_company_id_index ON public.system_settings USING btree (company_id);


--
-- Name: tasks_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tasks_company_id_index ON public.tasks USING btree (company_id);


--
-- Name: technician_action_logs_client_id_action_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX technician_action_logs_client_id_action_type_index ON public.technician_action_logs USING btree (client_id, action_type);


--
-- Name: technician_action_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX technician_action_logs_created_at_index ON public.technician_action_logs USING btree (created_at);


--
-- Name: technician_action_logs_reference_type_reference_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX technician_action_logs_reference_type_reference_id_index ON public.technician_action_logs USING btree (reference_type, reference_id);


--
-- Name: technician_action_logs_status_is_overridden_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX technician_action_logs_status_is_overridden_index ON public.technician_action_logs USING btree (status, is_overridden);


--
-- Name: technician_action_logs_technician_id_action_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX technician_action_logs_technician_id_action_type_index ON public.technician_action_logs USING btree (technician_id, action_type);


--
-- Name: technician_assignments_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX technician_assignments_company_id_index ON public.technician_assignments USING btree (company_id);


--
-- Name: technician_jobs_technician_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX technician_jobs_technician_id_status_index ON public.technician_jobs USING btree (technician_id, status);


--
-- Name: ticket_categories_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_categories_company_id_index ON public.ticket_categories USING btree (company_id);


--
-- Name: ticket_messages_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_messages_company_id_index ON public.ticket_messages USING btree (company_id);


--
-- Name: ticket_sla_timers_ticket_id_sla_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_sla_timers_ticket_id_sla_type_index ON public.ticket_sla_timers USING btree (ticket_id, sla_type);


--
-- Name: tickets_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_company_id_index ON public.tickets USING btree (company_id);


--
-- Name: tickets_status_priority_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_status_priority_index ON public.tickets USING btree (status, priority);


--
-- Name: user_menu_permissions_user_id_menu_key_submenu_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_menu_permissions_user_id_menu_key_submenu_key_index ON public.user_menu_permissions USING btree (user_id, menu_key, submenu_key);


--
-- Name: users_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_company_id_index ON public.users USING btree (company_id);


--
-- Name: vendor_ledgers_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendor_ledgers_company_id_index ON public.vendor_ledgers USING btree (company_id);


--
-- Name: vendors_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vendors_company_id_index ON public.vendors USING btree (company_id);


--
-- Name: voice_call_logs_client_id_call_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX voice_call_logs_client_id_call_type_index ON public.voice_call_logs USING btree (client_id, call_type);


--
-- Name: voice_call_settings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX voice_call_settings_company_id_index ON public.voice_call_settings USING btree (company_id);


--
-- Name: vpn_accounts_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vpn_accounts_company_id_index ON public.vpn_accounts USING btree (company_id);


--
-- Name: webhook_payment_logs_client_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX webhook_payment_logs_client_id_index ON public.webhook_payment_logs USING btree (client_id);


--
-- Name: webhook_payment_logs_gateway_trx_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX webhook_payment_logs_gateway_trx_id_index ON public.webhook_payment_logs USING btree (gateway, trx_id);


--
-- Name: webhook_payment_logs_reference_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX webhook_payment_logs_reference_index ON public.webhook_payment_logs USING btree (reference);


--
-- Name: webhook_payment_logs_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX webhook_payment_logs_status_index ON public.webhook_payment_logs USING btree (status);


--
-- Name: whatsapp_logs_client_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX whatsapp_logs_client_id_created_at_index ON public.whatsapp_logs USING btree (client_id, created_at);


--
-- Name: whatsapp_logs_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX whatsapp_logs_status_created_at_index ON public.whatsapp_logs USING btree (status, created_at);


--
-- Name: whatsapp_settings_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX whatsapp_settings_company_id_index ON public.whatsapp_settings USING btree (company_id);


--
-- Name: whatsapp_templates_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX whatsapp_templates_company_id_index ON public.whatsapp_templates USING btree (company_id);


--
-- Name: zones_company_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX zones_company_id_index ON public.zones USING btree (company_id);


--
-- Name: action_confirmations action_confirmations_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.action_confirmations
    ADD CONSTRAINT action_confirmations_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: activity_logs activity_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_logs
    ADD CONSTRAINT activity_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: admin_overrides admin_overrides_admin_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_overrides
    ADD CONSTRAINT admin_overrides_admin_id_foreign FOREIGN KEY (admin_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: admin_overrides admin_overrides_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_overrides
    ADD CONSTRAINT admin_overrides_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: admin_overrides admin_overrides_technician_action_log_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_overrides
    ADD CONSTRAINT admin_overrides_technician_action_log_id_foreign FOREIGN KEY (technician_action_log_id) REFERENCES public.technician_action_logs(id) ON DELETE CASCADE;


--
-- Name: advance_payment_rewards advance_payment_rewards_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payment_rewards
    ADD CONSTRAINT advance_payment_rewards_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: advance_payments advance_payments_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payments
    ADD CONSTRAINT advance_payments_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: advance_payments advance_payments_collected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payments
    ADD CONSTRAINT advance_payments_collected_by_foreign FOREIGN KEY (collected_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: advance_payments advance_payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_payments
    ADD CONSTRAINT advance_payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: advance_salaries advance_salaries_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_salaries
    ADD CONSTRAINT advance_salaries_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: advance_salaries advance_salaries_employee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.advance_salaries
    ADD CONSTRAINT advance_salaries_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE;


--
-- Name: agent_commissions agent_commissions_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_commissions
    ADD CONSTRAINT agent_commissions_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.agents(id) ON DELETE CASCADE;


--
-- Name: announcements announcements_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT announcements_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: api_refresh_tokens api_refresh_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_refresh_tokens
    ADD CONSTRAINT api_refresh_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: api_tokens api_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.api_tokens
    ADD CONSTRAINT api_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: application_documents application_documents_application_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_documents
    ADD CONSTRAINT application_documents_application_id_foreign FOREIGN KEY (application_id) REFERENCES public.new_connection_applications(id) ON DELETE CASCADE;


--
-- Name: application_documents application_documents_uploaded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_documents
    ADD CONSTRAINT application_documents_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: application_status_logs application_status_logs_application_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_status_logs
    ADD CONSTRAINT application_status_logs_application_id_foreign FOREIGN KEY (application_id) REFERENCES public.new_connection_applications(id) ON DELETE CASCADE;


--
-- Name: application_status_logs application_status_logs_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.application_status_logs
    ADD CONSTRAINT application_status_logs_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: area_outage_logs area_outage_logs_resolved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_outage_logs
    ADD CONSTRAINT area_outage_logs_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: area_outage_logs area_outage_logs_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_outage_logs
    ADD CONSTRAINT area_outage_logs_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE CASCADE;


--
-- Name: area_profitability area_profitability_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.area_profitability
    ADD CONSTRAINT area_profitability_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE CASCADE;


--
-- Name: areas areas_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.areas
    ADD CONSTRAINT areas_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: areas areas_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.areas
    ADD CONSTRAINT areas_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE CASCADE;


--
-- Name: attendances attendances_employee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.attendances
    ADD CONSTRAINT attendances_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE;


--
-- Name: backups backups_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.backups
    ADD CONSTRAINT backups_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bandwidth_queues bandwidth_queues_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bandwidth_queues
    ADD CONSTRAINT bandwidth_queues_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE CASCADE;


--
-- Name: bill_adjustments bill_adjustments_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_adjustments
    ADD CONSTRAINT bill_adjustments_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE CASCADE;


--
-- Name: bill_adjustments bill_adjustments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_adjustments
    ADD CONSTRAINT bill_adjustments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bill_corrections bill_corrections_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_corrections
    ADD CONSTRAINT bill_corrections_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bill_corrections bill_corrections_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_corrections
    ADD CONSTRAINT bill_corrections_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE CASCADE;


--
-- Name: bill_corrections bill_corrections_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_corrections
    ADD CONSTRAINT bill_corrections_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: bill_corrections bill_corrections_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_corrections
    ADD CONSTRAINT bill_corrections_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bill_items bill_items_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_items
    ADD CONSTRAINT bill_items_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE CASCADE;


--
-- Name: bill_splits bill_splits_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_splits
    ADD CONSTRAINT bill_splits_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE CASCADE;


--
-- Name: bill_splits bill_splits_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bill_splits
    ADD CONSTRAINT bill_splits_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: bills bills_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: bills bills_generated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bills
    ADD CONSTRAINT bills_generated_by_foreign FOREIGN KEY (generated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: boxes boxes_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.boxes
    ADD CONSTRAINT boxes_area_id_foreign FOREIGN KEY (area_id) REFERENCES public.areas(id) ON DELETE SET NULL;


--
-- Name: boxes boxes_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.boxes
    ADD CONSTRAINT boxes_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: branches branches_manager_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_manager_id_foreign FOREIGN KEY (manager_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: card_distributors card_distributors_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_distributors
    ADD CONSTRAINT card_distributors_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: card_transactions card_transactions_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_transactions
    ADD CONSTRAINT card_transactions_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: card_transactions card_transactions_distributor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_transactions
    ADD CONSTRAINT card_transactions_distributor_id_foreign FOREIGN KEY (distributor_id) REFERENCES public.card_distributors(id) ON DELETE SET NULL;


--
-- Name: card_transactions card_transactions_scratch_card_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.card_transactions
    ADD CONSTRAINT card_transactions_scratch_card_id_foreign FOREIGN KEY (scratch_card_id) REFERENCES public.scratch_cards(id) ON DELETE CASCADE;


--
-- Name: churn_predictions churn_predictions_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.churn_predictions
    ADD CONSTRAINT churn_predictions_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_activity_logs client_activity_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_activity_logs
    ADD CONSTRAINT client_activity_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_activity_logs client_activity_logs_performed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_activity_logs
    ADD CONSTRAINT client_activity_logs_performed_by_foreign FOREIGN KEY (performed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_additional_payments client_additional_payments_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_additional_payments
    ADD CONSTRAINT client_additional_payments_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_additional_payments client_additional_payments_collected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_additional_payments
    ADD CONSTRAINT client_additional_payments_collected_by_foreign FOREIGN KEY (collected_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_additional_payments client_additional_payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_additional_payments
    ADD CONSTRAINT client_additional_payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: client_agreements client_agreements_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_agreements
    ADD CONSTRAINT client_agreements_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_documents client_documents_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_documents
    ADD CONSTRAINT client_documents_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_expiry_logs client_expiry_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_expiry_logs
    ADD CONSTRAINT client_expiry_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_grace_periods client_grace_periods_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_grace_periods
    ADD CONSTRAINT client_grace_periods_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: client_grace_periods client_grace_periods_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_grace_periods
    ADD CONSTRAINT client_grace_periods_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_lifetime_values client_lifetime_values_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_lifetime_values
    ADD CONSTRAINT client_lifetime_values_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_line_shifts client_line_shifts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_line_shifts
    ADD CONSTRAINT client_line_shifts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_line_shifts client_line_shifts_processed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_line_shifts
    ADD CONSTRAINT client_line_shifts_processed_by_foreign FOREIGN KEY (processed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_packages client_packages_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_packages
    ADD CONSTRAINT client_packages_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_packages client_packages_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_packages
    ADD CONSTRAINT client_packages_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE CASCADE;


--
-- Name: client_promo_usage client_promo_usage_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_promo_usage
    ADD CONSTRAINT client_promo_usage_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE SET NULL;


--
-- Name: client_promo_usage client_promo_usage_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_promo_usage
    ADD CONSTRAINT client_promo_usage_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_promo_usage client_promo_usage_promo_code_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_promo_usage
    ADD CONSTRAINT client_promo_usage_promo_code_id_foreign FOREIGN KEY (promo_code_id) REFERENCES public.promo_codes(id) ON DELETE CASCADE;


--
-- Name: client_reconnections client_reconnections_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_reconnections
    ADD CONSTRAINT client_reconnections_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_reconnections client_reconnections_processed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_reconnections
    ADD CONSTRAINT client_reconnections_processed_by_foreign FOREIGN KEY (processed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_recovery_logs client_recovery_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recovery_logs
    ADD CONSTRAINT client_recovery_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_recovery_logs client_recovery_logs_recovered_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_recovery_logs
    ADD CONSTRAINT client_recovery_logs_recovered_by_foreign FOREIGN KEY (recovered_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: client_service_charges client_service_charges_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_service_charges
    ADD CONSTRAINT client_service_charges_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_service_charges client_service_charges_paid_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_service_charges
    ADD CONSTRAINT client_service_charges_paid_by_foreign FOREIGN KEY (paid_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_session_logs client_session_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_session_logs
    ADD CONSTRAINT client_session_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_status_logs client_status_logs_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_status_logs
    ADD CONSTRAINT client_status_logs_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: client_status_logs client_status_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_status_logs
    ADD CONSTRAINT client_status_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_timeline client_timeline_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_timeline
    ADD CONSTRAINT client_timeline_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: client_timeline client_timeline_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.client_timeline
    ADD CONSTRAINT client_timeline_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: clients clients_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.agents(id) ON DELETE SET NULL;


--
-- Name: clients clients_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_area_id_foreign FOREIGN KEY (area_id) REFERENCES public.areas(id) ON DELETE SET NULL;


--
-- Name: clients clients_box_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_box_id_foreign FOREIGN KEY (box_id) REFERENCES public.boxes(id) ON DELETE SET NULL;


--
-- Name: clients clients_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- Name: clients clients_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: clients clients_district_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_district_id_foreign FOREIGN KEY (district_id) REFERENCES public.districts(id) ON DELETE SET NULL;


--
-- Name: clients clients_olt_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_olt_id_foreign FOREIGN KEY (olt_id) REFERENCES public.olt_devices(id) ON DELETE SET NULL;


--
-- Name: clients clients_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE SET NULL;


--
-- Name: clients clients_pop_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_pop_id_foreign FOREIGN KEY (pop_id) REFERENCES public.pops(id) ON DELETE SET NULL;


--
-- Name: clients clients_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE SET NULL;


--
-- Name: clients clients_splitter_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_splitter_id_foreign FOREIGN KEY (splitter_id) REFERENCES public.splitters(id) ON DELETE SET NULL;


--
-- Name: clients clients_upazila_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_upazila_id_foreign FOREIGN KEY (upazila_id) REFERENCES public.upazilas(id) ON DELETE SET NULL;


--
-- Name: clients clients_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: clients clients_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: collection_targets collection_targets_collector_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_targets
    ADD CONSTRAINT collection_targets_collector_id_foreign FOREIGN KEY (collector_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: collection_targets collection_targets_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_targets
    ADD CONSTRAINT collection_targets_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: collection_transfers collection_transfers_from_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_transfers
    ADD CONSTRAINT collection_transfers_from_user_id_foreign FOREIGN KEY (from_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: collection_transfers collection_transfers_to_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_transfers
    ADD CONSTRAINT collection_transfers_to_account_id_foreign FOREIGN KEY (to_account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: collection_transfers collection_transfers_to_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_transfers
    ADD CONSTRAINT collection_transfers_to_user_id_foreign FOREIGN KEY (to_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: collection_transfers collection_transfers_verified_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collection_transfers
    ADD CONSTRAINT collection_transfers_verified_by_foreign FOREIGN KEY (verified_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: collections collections_collected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collections
    ADD CONSTRAINT collections_collected_by_foreign FOREIGN KEY (collected_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: collections collections_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.collections
    ADD CONSTRAINT collections_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE CASCADE;


--
-- Name: correction_audit_logs correction_audit_logs_performed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.correction_audit_logs
    ADD CONSTRAINT correction_audit_logs_performed_by_foreign FOREIGN KEY (performed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: credit_debit_notes credit_debit_notes_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_debit_notes
    ADD CONSTRAINT credit_debit_notes_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: credit_debit_notes credit_debit_notes_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_debit_notes
    ADD CONSTRAINT credit_debit_notes_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: credit_debit_notes credit_debit_notes_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_debit_notes
    ADD CONSTRAINT credit_debit_notes_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: credit_debit_notes credit_debit_notes_ledger_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_debit_notes
    ADD CONSTRAINT credit_debit_notes_ledger_entry_id_foreign FOREIGN KEY (ledger_entry_id) REFERENCES public.ledger_entries(id) ON DELETE SET NULL;


--
-- Name: daily_cash_closes daily_cash_closes_staff_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_cash_closes
    ADD CONSTRAINT daily_cash_closes_staff_user_id_foreign FOREIGN KEY (staff_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: daily_cash_closes daily_cash_closes_verified_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_cash_closes
    ADD CONSTRAINT daily_cash_closes_verified_by_foreign FOREIGN KEY (verified_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: damaged_items damaged_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_items
    ADD CONSTRAINT damaged_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: damaged_items damaged_items_reported_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_items
    ADD CONSTRAINT damaged_items_reported_by_foreign FOREIGN KEY (reported_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: damaged_items damaged_items_serial_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_items
    ADD CONSTRAINT damaged_items_serial_id_foreign FOREIGN KEY (serial_id) REFERENCES public.product_serials(id) ON DELETE SET NULL;


--
-- Name: damaged_products damaged_products_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_products
    ADD CONSTRAINT damaged_products_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: damaged_products damaged_products_reported_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.damaged_products
    ADD CONSTRAINT damaged_products_reported_by_foreign FOREIGN KEY (reported_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: designations designations_department_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.designations
    ADD CONSTRAINT designations_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL;


--
-- Name: device_inventory device_inventory_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_inventory
    ADD CONSTRAINT device_inventory_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: device_inventory device_inventory_deployed_to_client_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.device_inventory
    ADD CONSTRAINT device_inventory_deployed_to_client_foreign FOREIGN KEY (deployed_to_client) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: due_reminders due_reminders_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.due_reminders
    ADD CONSTRAINT due_reminders_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE CASCADE;


--
-- Name: due_reminders due_reminders_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.due_reminders
    ADD CONSTRAINT due_reminders_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: employee_collections employee_collections_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_collections
    ADD CONSTRAINT employee_collections_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: employee_performances employee_performances_employee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employee_performances
    ADD CONSTRAINT employee_performances_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE;


--
-- Name: employees employees_department_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL;


--
-- Name: employees employees_designation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_designation_id_foreign FOREIGN KEY (designation_id) REFERENCES public.designations(id) ON DELETE SET NULL;


--
-- Name: employees employees_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.employees
    ADD CONSTRAINT employees_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: events events_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.events
    ADD CONSTRAINT events_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: events events_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.events
    ADD CONSTRAINT events_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE SET NULL;


--
-- Name: expenses expenses_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: expenses expenses_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: expenses expenses_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: expenses expenses_expense_head_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expenses
    ADD CONSTRAINT expenses_expense_head_id_foreign FOREIGN KEY (expense_head_id) REFERENCES public.expense_heads(id) ON DELETE CASCADE;


--
-- Name: export_jobs export_jobs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.export_jobs
    ADD CONSTRAINT export_jobs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: family_group_members family_group_members_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_group_members
    ADD CONSTRAINT family_group_members_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: family_group_members family_group_members_family_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_group_members
    ADD CONSTRAINT family_group_members_family_group_id_foreign FOREIGN KEY (family_group_id) REFERENCES public.family_groups(id) ON DELETE CASCADE;


--
-- Name: family_groups family_groups_primary_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.family_groups
    ADD CONSTRAINT family_groups_primary_client_id_foreign FOREIGN KEY (primary_client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: fiber_codes fiber_codes_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_codes
    ADD CONSTRAINT fiber_codes_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE SET NULL;


--
-- Name: fiber_histories fiber_histories_fiber_stock_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_histories
    ADD CONSTRAINT fiber_histories_fiber_stock_id_foreign FOREIGN KEY (fiber_stock_id) REFERENCES public.fiber_stocks(id) ON DELETE CASCADE;


--
-- Name: fiber_stocks fiber_stocks_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fiber_stocks
    ADD CONSTRAINT fiber_stocks_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE CASCADE;


--
-- Name: field_mapping_templates field_mapping_templates_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.field_mapping_templates
    ADD CONSTRAINT field_mapping_templates_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: fund_transfers fund_transfers_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fund_transfers
    ADD CONSTRAINT fund_transfers_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: fund_transfers fund_transfers_from_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fund_transfers
    ADD CONSTRAINT fund_transfers_from_account_id_foreign FOREIGN KEY (from_account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: fund_transfers fund_transfers_to_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fund_transfers
    ADD CONSTRAINT fund_transfers_to_account_id_foreign FOREIGN KEY (to_account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: hotspot_templates hotspot_templates_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotspot_templates
    ADD CONSTRAINT hotspot_templates_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: hotspot_templates hotspot_templates_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotspot_templates
    ADD CONSTRAINT hotspot_templates_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: import_jobs import_jobs_mapping_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_mapping_template_id_foreign FOREIGN KEY (mapping_template_id) REFERENCES public.field_mapping_templates(id) ON DELETE SET NULL;


--
-- Name: import_jobs import_jobs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: import_logs import_logs_import_job_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_logs
    ADD CONSTRAINT import_logs_import_job_id_foreign FOREIGN KEY (import_job_id) REFERENCES public.import_jobs(id) ON DELETE CASCADE;


--
-- Name: incomes incomes_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.incomes
    ADD CONSTRAINT incomes_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: incomes incomes_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.incomes
    ADD CONSTRAINT incomes_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: incomes incomes_income_head_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.incomes
    ADD CONSTRAINT incomes_income_head_id_foreign FOREIGN KEY (income_head_id) REFERENCES public.income_heads(id) ON DELETE CASCADE;


--
-- Name: instrument_categories instrument_categories_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instrument_categories
    ADD CONSTRAINT instrument_categories_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.instrument_categories(id) ON DELETE CASCADE;


--
-- Name: instruments instruments_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.instruments
    ADD CONSTRAINT instruments_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE SET NULL;


--
-- Name: integrity_check_logs integrity_check_logs_run_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.integrity_check_logs
    ADD CONSTRAINT integrity_check_logs_run_by_foreign FOREIGN KEY (run_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: internet_quality_scores internet_quality_scores_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.internet_quality_scores
    ADD CONSTRAINT internet_quality_scores_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ip_allocations ip_allocations_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_allocations
    ADD CONSTRAINT ip_allocations_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ip_allocations ip_allocations_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_allocations
    ADD CONSTRAINT ip_allocations_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: ip_allocations ip_allocations_ip_pool_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_allocations
    ADD CONSTRAINT ip_allocations_ip_pool_id_foreign FOREIGN KEY (ip_pool_id) REFERENCES public.ip_pools(id) ON DELETE CASCADE;


--
-- Name: ip_pools ip_pools_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_pools
    ADD CONSTRAINT ip_pools_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- Name: ip_pools ip_pools_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_pools
    ADD CONSTRAINT ip_pools_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: ip_pools ip_pools_pop_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_pools
    ADD CONSTRAINT ip_pools_pop_id_foreign FOREIGN KEY (pop_id) REFERENCES public.pops(id) ON DELETE SET NULL;


--
-- Name: ip_pools ip_pools_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_pools
    ADD CONSTRAINT ip_pools_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: ip_restrictions ip_restrictions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_restrictions
    ADD CONSTRAINT ip_restrictions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ip_restrictions ip_restrictions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ip_restrictions
    ADD CONSTRAINT ip_restrictions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: job_failure_logs job_failure_logs_resolved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_failure_logs
    ADD CONSTRAINT job_failure_logs_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: job_photos job_photos_technician_job_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_photos
    ADD CONSTRAINT job_photos_technician_job_id_foreign FOREIGN KEY (technician_job_id) REFERENCES public.technician_jobs(id) ON DELETE CASCADE;


--
-- Name: knowledge_base_articles knowledge_base_articles_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.knowledge_base_categories(id) ON DELETE CASCADE;


--
-- Name: knowledge_base_articles knowledge_base_articles_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.knowledge_base_articles
    ADD CONSTRAINT knowledge_base_articles_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: leaves leaves_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leaves
    ADD CONSTRAINT leaves_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: leaves leaves_employee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.leaves
    ADD CONSTRAINT leaves_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE;


--
-- Name: ledger_entries ledger_entries_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ledger_entries
    ADD CONSTRAINT ledger_entries_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: ledger_entries ledger_entries_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ledger_entries
    ADD CONSTRAINT ledger_entries_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: loan_payments loan_payments_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_payments
    ADD CONSTRAINT loan_payments_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: loan_payments loan_payments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_payments
    ADD CONSTRAINT loan_payments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: loan_payments loan_payments_loan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_payments
    ADD CONSTRAINT loan_payments_loan_id_foreign FOREIGN KEY (loan_id) REFERENCES public.loans(id) ON DELETE CASCADE;


--
-- Name: loans loans_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: loans loans_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: login_logs login_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.login_logs
    ADD CONSTRAINT login_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: mac_bindings mac_bindings_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mac_bindings
    ADD CONSTRAINT mac_bindings_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: mac_bindings mac_bindings_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mac_bindings
    ADD CONSTRAINT mac_bindings_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE CASCADE;


--
-- Name: maintenance_modes maintenance_modes_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_modes
    ADD CONSTRAINT maintenance_modes_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: maintenance_modes maintenance_modes_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.maintenance_modes
    ADD CONSTRAINT maintenance_modes_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE CASCADE;


--
-- Name: manager_assignments manager_assignments_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.manager_assignments
    ADD CONSTRAINT manager_assignments_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE CASCADE;


--
-- Name: manager_assignments manager_assignments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.manager_assignments
    ADD CONSTRAINT manager_assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: menu_permissions menu_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.menu_permissions
    ADD CONSTRAINT menu_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: migration_router_snapshots migration_router_snapshots_billing_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_router_snapshots
    ADD CONSTRAINT migration_router_snapshots_billing_client_id_foreign FOREIGN KEY (billing_client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: migration_router_snapshots migration_router_snapshots_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_router_snapshots
    ADD CONSTRAINT migration_router_snapshots_device_id_foreign FOREIGN KEY (device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: migration_router_snapshots migration_router_snapshots_migration_run_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_router_snapshots
    ADD CONSTRAINT migration_router_snapshots_migration_run_id_foreign FOREIGN KEY (migration_run_id) REFERENCES public.migration_runs(id) ON DELETE CASCADE;


--
-- Name: migration_runs migration_runs_import_job_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_runs
    ADD CONSTRAINT migration_runs_import_job_id_foreign FOREIGN KEY (import_job_id) REFERENCES public.import_jobs(id) ON DELETE SET NULL;


--
-- Name: migration_runs migration_runs_initiated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migration_runs
    ADD CONSTRAINT migration_runs_initiated_by_foreign FOREIGN KEY (initiated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: mikrotik_action_logs mikrotik_action_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_action_logs
    ADD CONSTRAINT mikrotik_action_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: mikrotik_action_logs mikrotik_action_logs_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_action_logs
    ADD CONSTRAINT mikrotik_action_logs_device_id_foreign FOREIGN KEY (device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: mikrotik_action_logs mikrotik_action_logs_triggered_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_action_logs
    ADD CONSTRAINT mikrotik_action_logs_triggered_by_foreign FOREIGN KEY (triggered_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: mikrotik_bandwidth_usage mikrotik_bandwidth_usage_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_bandwidth_usage
    ADD CONSTRAINT mikrotik_bandwidth_usage_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: mikrotik_devices mikrotik_devices_vpn_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_devices
    ADD CONSTRAINT mikrotik_devices_vpn_account_id_foreign FOREIGN KEY (vpn_account_id) REFERENCES public.vpn_accounts(id) ON DELETE SET NULL;


--
-- Name: mikrotik_devices mikrotik_devices_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_devices
    ADD CONSTRAINT mikrotik_devices_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: mikrotik_realtime_sessions mikrotik_realtime_sessions_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_realtime_sessions
    ADD CONSTRAINT mikrotik_realtime_sessions_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: mikrotik_realtime_sessions mikrotik_realtime_sessions_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_realtime_sessions
    ADD CONSTRAINT mikrotik_realtime_sessions_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE CASCADE;


--
-- Name: mikrotik_router_stats mikrotik_router_stats_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_router_stats
    ADD CONSTRAINT mikrotik_router_stats_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE CASCADE;


--
-- Name: mikrotik_sessions mikrotik_sessions_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sessions
    ADD CONSTRAINT mikrotik_sessions_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: mikrotik_sessions mikrotik_sessions_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sessions
    ADD CONSTRAINT mikrotik_sessions_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE CASCADE;


--
-- Name: mikrotik_sync_items mikrotik_sync_items_sync_run_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sync_items
    ADD CONSTRAINT mikrotik_sync_items_sync_run_id_foreign FOREIGN KEY (sync_run_id) REFERENCES public.mikrotik_sync_runs(id) ON DELETE CASCADE;


--
-- Name: mikrotik_sync_runs mikrotik_sync_runs_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sync_runs
    ADD CONSTRAINT mikrotik_sync_runs_device_id_foreign FOREIGN KEY (device_id) REFERENCES public.mikrotik_devices(id) ON DELETE CASCADE;


--
-- Name: mikrotik_sync_runs mikrotik_sync_runs_initiated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_sync_runs
    ADD CONSTRAINT mikrotik_sync_runs_initiated_by_foreign FOREIGN KEY (initiated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: mikrotik_users mikrotik_users_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_users
    ADD CONSTRAINT mikrotik_users_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: mikrotik_users mikrotik_users_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mikrotik_users
    ADD CONSTRAINT mikrotik_users_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE CASCADE;


--
-- Name: minute_sales minute_sales_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_sales
    ADD CONSTRAINT minute_sales_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: minute_sales minute_sales_minute_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_sales
    ADD CONSTRAINT minute_sales_minute_package_id_foreign FOREIGN KEY (minute_package_id) REFERENCES public.minute_packages(id) ON DELETE CASCADE;


--
-- Name: minute_sales minute_sales_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_sales
    ADD CONSTRAINT minute_sales_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE SET NULL;


--
-- Name: minute_sales minute_sales_sold_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.minute_sales
    ADD CONSTRAINT minute_sales_sold_by_foreign FOREIGN KEY (sold_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: month_closes month_closes_closed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.month_closes
    ADD CONSTRAINT month_closes_closed_by_foreign FOREIGN KEY (closed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_box_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_box_id_foreign FOREIGN KEY (box_id) REFERENCES public.boxes(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_created_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_created_client_id_foreign FOREIGN KEY (created_client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_district_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_district_id_foreign FOREIGN KEY (district_id) REFERENCES public.districts(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_olt_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_olt_device_id_foreign FOREIGN KEY (olt_device_id) REFERENCES public.olt_devices(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: new_connection_applications new_connection_applications_upazila_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_upazila_id_foreign FOREIGN KEY (upazila_id) REFERENCES public.upazilas(id) ON DELETE SET NULL;


--
-- Name: new_connection_applications new_connection_applications_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.new_connection_applications
    ADD CONSTRAINT new_connection_applications_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: nid_verifications nid_verifications_application_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nid_verifications
    ADD CONSTRAINT nid_verifications_application_id_foreign FOREIGN KEY (application_id) REFERENCES public.new_connection_applications(id) ON DELETE CASCADE;


--
-- Name: nid_verifications nid_verifications_verified_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nid_verifications
    ADD CONSTRAINT nid_verifications_verified_by_foreign FOREIGN KEY (verified_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: notifications notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: onu_devices onu_devices_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.onu_devices
    ADD CONSTRAINT onu_devices_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: package_change_requests package_change_requests_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_change_requests
    ADD CONSTRAINT package_change_requests_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: package_change_requests package_change_requests_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_change_requests
    ADD CONSTRAINT package_change_requests_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: package_change_requests package_change_requests_current_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_change_requests
    ADD CONSTRAINT package_change_requests_current_package_id_foreign FOREIGN KEY (current_package_id) REFERENCES public.packages(id) ON DELETE CASCADE;


--
-- Name: package_change_requests package_change_requests_requested_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_change_requests
    ADD CONSTRAINT package_change_requests_requested_package_id_foreign FOREIGN KEY (requested_package_id) REFERENCES public.packages(id) ON DELETE CASCADE;


--
-- Name: package_speeds package_speeds_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.package_speeds
    ADD CONSTRAINT package_speeds_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE CASCADE;


--
-- Name: payment_corrections payment_corrections_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_corrections
    ADD CONSTRAINT payment_corrections_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payment_corrections payment_corrections_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_corrections
    ADD CONSTRAINT payment_corrections_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: payment_corrections payment_corrections_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_corrections
    ADD CONSTRAINT payment_corrections_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE CASCADE;


--
-- Name: payment_corrections payment_corrections_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_corrections
    ADD CONSTRAINT payment_corrections_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payment_fraud_alerts payment_fraud_alerts_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_fraud_alerts
    ADD CONSTRAINT payment_fraud_alerts_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE CASCADE;


--
-- Name: payment_fraud_alerts payment_fraud_alerts_resolved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_fraud_alerts
    ADD CONSTRAINT payment_fraud_alerts_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payment_gateway_transactions payment_gateway_transactions_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_gateway_transactions
    ADD CONSTRAINT payment_gateway_transactions_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE SET NULL;


--
-- Name: payment_gateway_transactions payment_gateway_transactions_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_gateway_transactions
    ADD CONSTRAINT payment_gateway_transactions_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: payment_links payment_links_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_links
    ADD CONSTRAINT payment_links_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE SET NULL;


--
-- Name: payment_links payment_links_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_links
    ADD CONSTRAINT payment_links_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: payment_links payment_links_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_links
    ADD CONSTRAINT payment_links_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payment_links payment_links_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_links
    ADD CONSTRAINT payment_links_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE SET NULL;


--
-- Name: payment_method_perms payment_method_perms_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_method_perms
    ADD CONSTRAINT payment_method_perms_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: payment_method_perms payment_method_perms_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_method_perms
    ADD CONSTRAINT payment_method_perms_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE CASCADE;


--
-- Name: payment_method_perms payment_method_perms_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_method_perms
    ADD CONSTRAINT payment_method_perms_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: payment_method_perms payment_method_perms_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_method_perms
    ADD CONSTRAINT payment_method_perms_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: payments payments_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_bill_id_foreign FOREIGN KEY (bill_id) REFERENCES public.bills(id) ON DELETE SET NULL;


--
-- Name: payments payments_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: payments payments_collected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_collected_by_foreign FOREIGN KEY (collected_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payments payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: permission_audit_logs permission_audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_audit_logs
    ADD CONSTRAINT permission_audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: planned_maintenances planned_maintenances_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planned_maintenances
    ADD CONSTRAINT planned_maintenances_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: planned_maintenances planned_maintenances_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.planned_maintenances
    ADD CONSTRAINT planned_maintenances_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: pops pops_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pops
    ADD CONSTRAINT pops_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- Name: pops pops_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pops
    ADD CONSTRAINT pops_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: pops pops_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pops
    ADD CONSTRAINT pops_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: port_forwards port_forwards_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.port_forwards
    ADD CONSTRAINT port_forwards_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: port_forwards port_forwards_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.port_forwards
    ADD CONSTRAINT port_forwards_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: port_forwards port_forwards_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.port_forwards
    ADD CONSTRAINT port_forwards_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: product_serials product_serials_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_serials
    ADD CONSTRAINT product_serials_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: product_serials product_serials_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_serials
    ADD CONSTRAINT product_serials_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_serials product_serials_purchase_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_serials
    ADD CONSTRAINT product_serials_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES public.purchases(id) ON DELETE SET NULL;


--
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.product_categories(id) ON DELETE SET NULL;


--
-- Name: purchase_items purchase_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items
    ADD CONSTRAINT purchase_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: purchase_items purchase_items_purchase_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchase_items
    ADD CONSTRAINT purchase_items_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES public.purchases(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: purchases purchases_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE SET NULL;


--
-- Name: refunds refunds_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: refunds refunds_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE SET NULL;


--
-- Name: refunds refunds_processed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refunds
    ADD CONSTRAINT refunds_processed_by_foreign FOREIGN KEY (processed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: report_logs report_logs_generated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.report_logs
    ADD CONSTRAINT report_logs_generated_by_foreign FOREIGN KEY (generated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: request_tokens request_tokens_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request_tokens
    ADD CONSTRAINT request_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reseller_bill_payments reseller_bill_payments_received_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bill_payments
    ADD CONSTRAINT reseller_bill_payments_received_by_foreign FOREIGN KEY (received_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reseller_bill_payments reseller_bill_payments_reseller_bill_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bill_payments
    ADD CONSTRAINT reseller_bill_payments_reseller_bill_id_foreign FOREIGN KEY (reseller_bill_id) REFERENCES public.reseller_bills(id) ON DELETE SET NULL;


--
-- Name: reseller_bill_payments reseller_bill_payments_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bill_payments
    ADD CONSTRAINT reseller_bill_payments_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_billing_configs reseller_billing_configs_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_billing_configs
    ADD CONSTRAINT reseller_billing_configs_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_bills reseller_bills_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_bills
    ADD CONSTRAINT reseller_bills_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_boundaries reseller_boundaries_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_boundaries
    ADD CONSTRAINT reseller_boundaries_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reseller_boundaries reseller_boundaries_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_boundaries
    ADD CONSTRAINT reseller_boundaries_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE CASCADE;


--
-- Name: reseller_client_recharges reseller_client_recharges_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_client_recharges
    ADD CONSTRAINT reseller_client_recharges_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: reseller_client_recharges reseller_client_recharges_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_client_recharges
    ADD CONSTRAINT reseller_client_recharges_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE SET NULL;


--
-- Name: reseller_client_recharges reseller_client_recharges_recharged_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_client_recharges
    ADD CONSTRAINT reseller_client_recharges_recharged_by_foreign FOREIGN KEY (recharged_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reseller_client_recharges reseller_client_recharges_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_client_recharges
    ADD CONSTRAINT reseller_client_recharges_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_clients reseller_clients_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_clients
    ADD CONSTRAINT reseller_clients_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: reseller_clients reseller_clients_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_clients
    ADD CONSTRAINT reseller_clients_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_commissions reseller_commissions_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_commissions
    ADD CONSTRAINT reseller_commissions_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reseller_daily_bills reseller_daily_bills_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_daily_bills
    ADD CONSTRAINT reseller_daily_bills_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_packages reseller_packages_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_packages
    ADD CONSTRAINT reseller_packages_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE CASCADE;


--
-- Name: reseller_packages reseller_packages_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_packages
    ADD CONSTRAINT reseller_packages_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_payments reseller_payments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_payments
    ADD CONSTRAINT reseller_payments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reseller_payments reseller_payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_payments
    ADD CONSTRAINT reseller_payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: reseller_payments reseller_payments_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_payments
    ADD CONSTRAINT reseller_payments_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_wallet_transactions reseller_wallet_transactions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_wallet_transactions
    ADD CONSTRAINT reseller_wallet_transactions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reseller_wallet_transactions reseller_wallet_transactions_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_wallet_transactions
    ADD CONSTRAINT reseller_wallet_transactions_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: reseller_wallets reseller_wallets_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reseller_wallets
    ADD CONSTRAINT reseller_wallets_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: resellers resellers_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resellers
    ADD CONSTRAINT resellers_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- Name: resellers resellers_parent_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resellers
    ADD CONSTRAINT resellers_parent_reseller_id_foreign FOREIGN KEY (parent_reseller_id) REFERENCES public.resellers(id) ON DELETE SET NULL;


--
-- Name: resellers resellers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resellers
    ADD CONSTRAINT resellers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: resellers resellers_zone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resellers
    ADD CONSTRAINT resellers_zone_id_foreign FOREIGN KEY (zone_id) REFERENCES public.zones(id) ON DELETE SET NULL;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_granted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_granted_by_foreign FOREIGN KEY (granted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: role_permissions role_permissions_revoked_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_revoked_by_foreign FOREIGN KEY (revoked_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: salaries salaries_employee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salaries
    ADD CONSTRAINT salaries_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE;


--
-- Name: salary_payments salary_payments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salary_payments
    ADD CONSTRAINT salary_payments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: salary_payments salary_payments_payment_method_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salary_payments
    ADD CONSTRAINT salary_payments_payment_method_id_foreign FOREIGN KEY (payment_method_id) REFERENCES public.payment_methods(id) ON DELETE SET NULL;


--
-- Name: salary_payments salary_payments_salary_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salary_payments
    ADD CONSTRAINT salary_payments_salary_id_foreign FOREIGN KEY (salary_id) REFERENCES public.salaries(id) ON DELETE CASCADE;


--
-- Name: scratch_card_batches scratch_card_batches_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_card_batches
    ADD CONSTRAINT scratch_card_batches_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: scratch_card_batches scratch_card_batches_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_card_batches
    ADD CONSTRAINT scratch_card_batches_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE SET NULL;


--
-- Name: scratch_cards scratch_cards_batch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards
    ADD CONSTRAINT scratch_cards_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES public.scratch_card_batches(id) ON DELETE CASCADE;


--
-- Name: scratch_cards scratch_cards_distributor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards
    ADD CONSTRAINT scratch_cards_distributor_id_foreign FOREIGN KEY (distributor_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: scratch_cards scratch_cards_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards
    ADD CONSTRAINT scratch_cards_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE SET NULL;


--
-- Name: scratch_cards scratch_cards_used_by_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.scratch_cards
    ADD CONSTRAINT scratch_cards_used_by_client_id_foreign FOREIGN KEY (used_by_client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: service_ratings service_ratings_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: service_ratings service_ratings_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: service_ratings service_ratings_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_ratings
    ADD CONSTRAINT service_ratings_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: session_logs session_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.session_logs
    ADD CONSTRAINT session_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: slow_speed_alerts slow_speed_alerts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.slow_speed_alerts
    ADD CONSTRAINT slow_speed_alerts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: sms_logs sms_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_logs
    ADD CONSTRAINT sms_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: sms_logs sms_logs_sent_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_logs
    ADD CONSTRAINT sms_logs_sent_by_foreign FOREIGN KEY (sent_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: sms_schedules sms_schedules_sms_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sms_schedules
    ADD CONSTRAINT sms_schedules_sms_template_id_foreign FOREIGN KEY (sms_template_id) REFERENCES public.sms_templates(id) ON DELETE SET NULL;


--
-- Name: speed_boost_requests speed_boost_requests_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.speed_boost_requests
    ADD CONSTRAINT speed_boost_requests_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: speed_boost_requests speed_boost_requests_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.speed_boost_requests
    ADD CONSTRAINT speed_boost_requests_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: speed_test_logs speed_test_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.speed_test_logs
    ADD CONSTRAINT speed_test_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: splitters splitters_olt_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.splitters
    ADD CONSTRAINT splitters_olt_device_id_foreign FOREIGN KEY (olt_device_id) REFERENCES public.olt_devices(id) ON DELETE SET NULL;


--
-- Name: splitters splitters_parent_splitter_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.splitters
    ADD CONSTRAINT splitters_parent_splitter_id_foreign FOREIGN KEY (parent_splitter_id) REFERENCES public.splitters(id) ON DELETE SET NULL;


--
-- Name: staff_dues staff_dues_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_dues
    ADD CONSTRAINT staff_dues_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: staff_receipts staff_receipts_received_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_receipts
    ADD CONSTRAINT staff_receipts_received_by_foreign FOREIGN KEY (received_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: staff_receipts staff_receipts_staff_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_receipts
    ADD CONSTRAINT staff_receipts_staff_id_foreign FOREIGN KEY (staff_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: staff_submissions staff_submissions_received_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_submissions
    ADD CONSTRAINT staff_submissions_received_by_foreign FOREIGN KEY (received_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: staff_submissions staff_submissions_staff_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staff_submissions
    ADD CONSTRAINT staff_submissions_staff_user_id_foreign FOREIGN KEY (staff_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: stock_logs stock_logs_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_logs
    ADD CONSTRAINT stock_logs_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stock_logs stock_logs_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_logs
    ADD CONSTRAINT stock_logs_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: stock_movements stock_movements_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: stocks stocks_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks
    ADD CONSTRAINT stocks_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stocks stocks_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stocks
    ADD CONSTRAINT stocks_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: sub_reseller_commissions sub_reseller_commissions_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_reseller_commissions
    ADD CONSTRAINT sub_reseller_commissions_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: sub_reseller_commissions sub_reseller_commissions_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_reseller_commissions
    ADD CONSTRAINT sub_reseller_commissions_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE SET NULL;


--
-- Name: sub_reseller_commissions sub_reseller_commissions_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_reseller_commissions
    ADD CONSTRAINT sub_reseller_commissions_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: sub_reseller_commissions sub_reseller_commissions_sub_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sub_reseller_commissions
    ADD CONSTRAINT sub_reseller_commissions_sub_reseller_id_foreign FOREIGN KEY (sub_reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: tasks tasks_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tasks tasks_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: technician_action_logs technician_action_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_action_logs
    ADD CONSTRAINT technician_action_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: technician_action_logs technician_action_logs_overridden_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_action_logs
    ADD CONSTRAINT technician_action_logs_overridden_by_foreign FOREIGN KEY (overridden_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: technician_action_logs technician_action_logs_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_action_logs
    ADD CONSTRAINT technician_action_logs_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: technician_assignments technician_assignments_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_assignments
    ADD CONSTRAINT technician_assignments_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: technician_assignments technician_assignments_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_assignments
    ADD CONSTRAINT technician_assignments_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: technician_jobs technician_jobs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_jobs
    ADD CONSTRAINT technician_jobs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: technician_jobs technician_jobs_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_jobs
    ADD CONSTRAINT technician_jobs_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: technician_jobs technician_jobs_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_jobs
    ADD CONSTRAINT technician_jobs_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: technician_shifts technician_shifts_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.technician_shifts
    ADD CONSTRAINT technician_shifts_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_assignments ticket_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_assignments ticket_assignments_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_assignments ticket_assignments_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_assignments
    ADD CONSTRAINT ticket_assignments_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_escalations ticket_escalations_escalated_from_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_escalations
    ADD CONSTRAINT ticket_escalations_escalated_from_foreign FOREIGN KEY (escalated_from) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ticket_escalations ticket_escalations_escalated_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_escalations
    ADD CONSTRAINT ticket_escalations_escalated_to_foreign FOREIGN KEY (escalated_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ticket_escalations ticket_escalations_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_escalations
    ADD CONSTRAINT ticket_escalations_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_feedback ticket_feedback_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_feedback
    ADD CONSTRAINT ticket_feedback_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_messages ticket_messages_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages
    ADD CONSTRAINT ticket_messages_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_messages ticket_messages_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_messages
    ADD CONSTRAINT ticket_messages_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ticket_resolution_scores ticket_resolution_scores_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_resolution_scores
    ADD CONSTRAINT ticket_resolution_scores_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.ticket_categories(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_technician_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_technician_id_foreign FOREIGN KEY (technician_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: upazilas upazilas_district_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.upazilas
    ADD CONSTRAINT upazilas_district_id_foreign FOREIGN KEY (district_id) REFERENCES public.districts(id) ON DELETE CASCADE;


--
-- Name: uptime_logs uptime_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.uptime_logs
    ADD CONSTRAINT uptime_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE CASCADE;


--
-- Name: users users_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- Name: vendor_ledgers vendor_ledgers_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_ledgers
    ADD CONSTRAINT vendor_ledgers_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: vendor_ledgers vendor_ledgers_vendor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vendor_ledgers
    ADD CONSTRAINT vendor_ledgers_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES public.vendors(id) ON DELETE CASCADE;


--
-- Name: voice_call_logs voice_call_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voice_call_logs
    ADD CONSTRAINT voice_call_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: vpn_accounts vpn_accounts_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vpn_accounts
    ADD CONSTRAINT vpn_accounts_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: vpn_accounts vpn_accounts_mikrotik_device_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vpn_accounts
    ADD CONSTRAINT vpn_accounts_mikrotik_device_id_foreign FOREIGN KEY (mikrotik_device_id) REFERENCES public.mikrotik_devices(id) ON DELETE SET NULL;


--
-- Name: webhook_payment_logs webhook_payment_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_payment_logs
    ADD CONSTRAINT webhook_payment_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: webhook_payment_logs webhook_payment_logs_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_payment_logs
    ADD CONSTRAINT webhook_payment_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE SET NULL;


--
-- Name: webhook_payment_logs webhook_payment_logs_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_payment_logs
    ADD CONSTRAINT webhook_payment_logs_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE SET NULL;


--
-- Name: whatsapp_logs whatsapp_logs_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whatsapp_logs
    ADD CONSTRAINT whatsapp_logs_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON DELETE SET NULL;


--
-- Name: zones zones_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.zones
    ADD CONSTRAINT zones_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- Name: zones zones_reseller_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.zones
    ADD CONSTRAINT zones_reseller_id_foreign FOREIGN KEY (reseller_id) REFERENCES public.resellers(id) ON DELETE CASCADE;


--
-- Name: zones zones_upazila_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.zones
    ADD CONSTRAINT zones_upazila_id_foreign FOREIGN KEY (upazila_id) REFERENCES public.upazilas(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict TdrKIDY4my5RRp8zpuFhhB0B7WGvdijyG75MHqZzeiBbXl8aRkfzW1rcdWzYj76

--
-- PostgreSQL database dump
--

\restrict imMCGrlxfvCmSe0RzoKQz5qs1GWHrB2pmhKL6t7MSwPnBwjoOoAmYxw6eixx7Ab

-- Dumped from database version 16.10
-- Dumped by pg_dump version 16.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2024_01_01_000001_create_users_table	1
2	2024_01_01_000002_create_cache_table	1
3	2024_01_01_000003_create_jobs_table	1
4	2024_01_01_000004_create_permission_tables	1
5	2024_01_01_000005_create_activity_logs_table	1
6	2024_01_01_000006_create_zones_areas_table	1
7	2024_01_01_000007_create_packages_table	1
8	2024_01_01_000008_create_clients_table	1
9	2024_01_01_000009_create_mikrotik_tables	1
10	2024_01_01_000010_create_billing_tables	1
11	2024_01_01_000011_create_accounts_tables	1
12	2024_01_01_000012_create_inventory_tables	1
13	2024_01_01_000013_create_support_tables	1
14	2024_01_01_000014_create_hrm_tables	1
15	2024_01_01_000015_create_reseller_tables	1
16	2024_01_01_000016_create_sms_settings_tables	1
17	2024_01_01_000017_add_foreign_keys	1
18	2024_01_01_000018_add_advanced_features	1
19	2024_01_02_000001_add_client_advanced_fields	1
20	2024_01_03_000001_create_payment_gateway_transactions_table	1
21	2024_01_04_000001_add_realtime_tracking_tables	1
22	2024_01_05_000001_create_new_connection_applications_tables	1
23	2024_01_20_000001_create_districts_and_upazilas_tables	1
24	2024_01_22_000001_add_application_details_to_clients	1
25	2024_01_23_000001_add_olt_onu_fields_to_clients	1
26	2026_01_21_201224_add_zone_ids_to_employees_table	1
27	2026_01_22_030426_create_staff_dues_table	1
28	2026_01_22_171637_create_olt_devices_table	1
29	2026_01_22_180000_add_status_to_mikrotik_devices	1
30	2026_01_22_190000_create_staff_receipts_table	1
31	2026_01_23_000001_add_zone_billing_to_resellers	1
32	2026_01_23_000001_update_new_connection_applications_nullable	1
33	2026_01_23_000002_make_technician_id_nullable	1
34	2026_01_23_020000_create_network_automation_tables	1
35	2026_01_23_030000_create_advanced_features_tables	1
36	2026_01_23_040000_create_network_monitoring_tables	1
37	2026_01_23_050000_create_comprehensive_features_tables	1
38	2026_01_23_060000_create_realtime_features_tables	1
39	2026_01_24_000001_add_termination_fields_to_resellers	1
40	2026_01_24_012737_add_discount_reason_to_bills_table	1
41	2026_01_24_015256_create_client_activity_logs_table	1
42	2026_01_24_100001_add_onu_details_to_applications	1
43	2026_01_24_184208_add_notes_to_payments_table	1
44	2026_01_24_200001_create_ledger_correction_tables	1
45	2026_01_24_210001_create_import_export_tables	1
46	2026_01_25_092842_add_billing_fields_to_payments_table	1
47	2026_01_25_093730_add_sms_control_fields	1
48	2026_01_25_095320_create_mikrotik_action_logs_table	1
49	2026_01_25_100000_create_integrity_check_logs_table	1
50	2026_01_25_103259_add_code_column_to_sms_templates_table	1
51	2026_01_25_200001_create_enterprise_features_tables	1
52	2026_01_26_004919_create_technician_action_logs_table	1
53	2026_01_26_004921_create_admin_overrides_table	1
54	2026_01_26_005808_create_system_permissions_table	1
55	2026_01_26_005809_create_role_permissions_table	1
56	2026_01_26_005931_create_permission_audit_logs_table	1
57	2026_01_26_071147_create_boxes_table	1
58	2026_01_26_081456_add_metadata_to_tickets_table	1
59	2026_01_26_081850_create_notifications_table	1
60	2026_01_27_004501_create_self_service_lookup_logs_table	1
61	2026_01_27_012651_create_mikrotik_simulator_users_table	1
62	2026_01_27_183015_seed_permission_roles	1
63	2026_01_28_000001_add_client_portal_features	1
64	2026_01_28_000001_add_prorated_fields_to_bills	1
65	2026_01_28_180000_fix_sms_logs_status_constraint	1
66	2026_01_30_000001_add_source_to_client_status_logs	1
67	2026_01_30_000002_create_sms_settings_table	1
68	2026_02_02_025429_add_document_fields_to_employees_table	1
69	2026_02_02_154621_create_announcements_table	1
70	2026_02_02_200000_create_client_additional_payments_table	1
71	2026_02_02_200100_add_custom_name_to_client_additional_payments	1
72	2026_02_03_164618_add_advance_applied_at_to_bills_table	1
73	2026_02_03_165448_create_events_table	1
74	2026_02_03_165448_create_tasks_table	1
75	2026_02_04_171528_add_connection_medium_to_clients_table	1
76	2026_02_04_171951_add_connection_medium_to_new_connection_applications_table	1
77	2026_02_04_211237_create_network_topology_tables	1
78	2026_02_06_000001_create_enterprise_upgrade_tables	1
79	2026_02_06_000002_pressure_test_fixes	1
80	2026_02_06_000003_create_missing_device_flows_tables	1
81	2026_02_06_211506_add_vpn_www_ppp_fields_to_mikrotik_devices_table	1
82	2026_02_06_212536_add_management_vpn_fields_to_vpn_accounts_table	1
83	2026_02_06_220000_add_router_type_to_mikrotik_devices_table	1
84	2026_02_07_000001_create_netbilling_features_tables	2
85	2026_02_09_030102_create_mikrotik_sync_runs_table	2
86	2026_02_10_000001_add_temporary_deadline_fields_to_clients	2
87	2026_02_12_000001_create_migration_runs_table	2
88	2026_02_13_142039_alter_sms_logs_response_column	2
89	2026_02_13_163911_create_automation_settings_table	2
90	2026_02_13_190502_create_vendors_table	2
91	2026_02_13_190503_create_products_table	2
92	2026_02_13_190505_create_product_serials_table	2
93	2026_02_13_190506_create_fiber_codes_table	2
94	2026_02_13_190507_create_fiber_usages_table	2
95	2026_02_14_172124_add_vendor_to_purchases_table	2
96	2026_02_14_190000_create_product_categories_table	2
97	2026_02_15_000000_add_deleted_at_to_product_categories	2
98	2026_02_15_000001_alter_products_table_for_isp_inventory	2
99	2026_02_15_000001_fix_product_categories_table	2
100	2026_02_15_010000_create_fiber_stocks_table	2
101	2026_02_15_010100_create_fiber_usages_table	2
102	2026_02_15_010200_create_fiber_histories_table	2
103	2026_02_15_234834_fix_fiber_usages_remove_fiber_code_id_requirement	2
104	2026_02_15_234934_add_brand_name_to_fiber_stocks_table	2
105	2026_02_16_000001_fix_inventory_data_consistency	2
106	2026_02_16_003541_add_instrument_to_fiber_stocks_table	2
107	2026_02_16_005210_remove_instrument_from_fiber_stocks_and_create_instruments_table	2
108	2026_02_16_011642_add_name_to_fiber_stocks_table	2
109	2026_02_16_020000_create_menu_permissions_table	2
110	2026_02_16_030733_create_instrument_categories_table	2
111	2026_02_16_050000_add_missing_inventory_columns	2
112	2026_02_16_160000_add_parent_id_to_instrument_categories	2
113	2026_02_16_232925_add_invoice_number_to_instruments_table	2
114	2026_02_16_234145_add_sub_category_to_instruments_table	2
115	2026_02_17_000716_create_instrument_out_logs_table	2
116	2026_02_17_013612_add_free_client_and_onu_charge_to_clients	2
117	2026_02_17_021755_add_client_filter_fields_to_clients_table	2
118	2026_02_18_100000_add_search_indexes_to_clients_table	2
119	2026_02_19_000001_add_profile_picture_to_clients_table	2
120	2026_02_19_010714_add_district_upazila_to_clients_and_applications	2
121	2026_02_19_100000_create_companies_table	2
122	2026_02_19_100001_add_company_id_to_all_tables	2
123	2026_02_19_100002_seed_default_company_and_super_admin	2
124	2026_02_20_125455_add_missing_columns_occupation_router_brand_uptime	2
125	2026_02_21_040000_add_last_status_change_to_clients_table	2
126	2026_02_22_235754_add_onu_source_to_clients_table	2
127	2026_02_23_015230_add_commission_to_card_transactions_table	2
128	2026_02_23_020000_create_client_expiry_logs_table	2
129	2026_02_23_020001_create_client_session_logs_table	2
130	2026_02_23_020002_add_billman_to_users_user_type_check	2
131	2026_02_23_031705_add_contact_person_and_opening_balance_to_vendors_table	2
132	2026_02_23_032000_fix_port_forwards_client_id_nullable	2
133	2026_02_23_041100_add_technician_id_to_clients_table	2
134	2026_02_26_220000_add_gender_mother_name_to_clients	2
135	2026_02_27_010654_add_connection_type_to_olt_devices_table	2
136	2026_02_27_015223_add_fiber_and_splitter_fields_to_clients_table	2
137	2026_02_27_015521_add_fiber_splitter_to_new_connection_applications	2
138	2026_02_27_020552_add_fiber_vendor_and_core_type_to_clients_table	2
139	2026_02_27_022844_add_onu_brand_to_clients_and_applications	2
140	2026_02_27_025703_add_expire_date_to_new_connection_applications	2
141	2026_02_27_030535_add_mikrotik_profile_to_packages	2
142	2026_02_27_124555_create_mikrotik_profiles_table	2
143	2026_02_27_165704_add_mikrotik_profile_name_to_clients_table	2
144	2026_02_27_174025_add_mikrotik_profile_name_to_new_connection_applications	2
145	2026_02_27_214827_add_allowed_menus_to_companies_table	2
146	2026_02_28_225601_create_webhook_payment_logs_table	2
147	2026_03_01_015500_add_upazila_id_to_zones_table	2
148	2026_03_01_021203_create_online_client_history_table	2
149	2026_03_01_030000_add_bandwidth_fields_to_resellers_table	2
150	2026_03_02_020354_add_quantity_to_instruments_table	2
151	2026_03_02_023007_add_purchaser_invoice_price_to_fiber_stocks	2
152	2026_03_02_023906_add_purchaser_to_instruments_table	2
153	2026_03_02_025834_add_note_to_fiber_histories_table	2
154	2026_03_02_054434_create_agents_table	2
155	2026_03_02_063900_add_agent_id_to_clients_table	2
156	2026_03_02_070812_add_type_to_agent_commissions_table	2
157	2026_03_02_072641_add_previous_mikrotik_profile_to_clients	2
158	2026_03_02_091624_add_has_serial_to_instrument_categories	2
159	2026_03_03_002614_add_money_receipt_no_to_payments_table	2
160	2026_03_03_003722_add_money_receipt_no_to_client_additional_payments_table	2
161	2026_03_03_004436_add_transaction_fields_to_client_additional_payments	2
162	2026_03_03_210005_add_box_id_to_new_connection_applications_table	2
163	2026_03_04_003906_reset_all_clients_connection_status_to_offline	2
164	2026_03_04_045820_add_fiber_required_to_applications_and_clients	2
165	2026_03_04_054217_add_occupation_to_new_connection_applications	2
166	2026_03_04_061741_create_client_service_charges_table	2
167	2026_03_04_062000_create_dropdown_options_table	2
168	2026_03_09_000001_create_reseller_recharge_system_tables	2
169	2026_03_09_100001_add_mac_reseller_config_fields	2
170	2026_03_10_000001_update_connection_type_check_constraint	2
171	2026_03_10_050307_add_auto_renewal_to_clients_table	2
172	2026_03_10_051120_add_transaction_type_to_reseller_client_recharges	2
173	2026_03_10_171948_add_ppp_short_code_to_resellers_table	2
174	2026_03_10_233431_add_referral_commission_percent_to_resellers_table	2
175	2026_03_11_004212_add_flexible_min_days_to_resellers_table	2
176	2026_03_11_210851_add_reseller_id_to_zones_areas_table	2
177	2026_03_11_220000_add_bandwidth_monthly_rate_to_resellers	2
178	2026_03_12_000001_fix_employee_id_unique_per_company	2
179	2026_03_12_140242_create_payment_method_perms_table	2
180	2026_03_12_200000_fix_sms_templates_code_unique_per_company	2
181	2026_03_12_200001_fix_payment_methods_code_unique_per_company	2
182	2026_03_12_300000_create_all_postsetup_tables_permanently	2
183	2026_03_12_300001_add_all_postsetup_columns_permanently	2
184	2026_03_13_002305_add_source_message_to_network_event_logs	3
185	2026_03_13_100000_add_allowed_features_to_resellers	4
186	2026_03_13_200000_fix_reseller_data_company_id	5
187	2026_03_13_041457_fix_mikrotik_devices_company_id	6
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 187, true);


--
-- PostgreSQL database dump complete
--

\unrestrict imMCGrlxfvCmSe0RzoKQz5qs1GWHrB2pmhKL6t7MSwPnBwjoOoAmYxw6eixx7Ab

