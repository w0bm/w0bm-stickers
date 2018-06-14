CREATE EXTENSION pgcrypto;

CREATE TABLE orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    street TEXT NOT NULL,
    house_number VARCHAR(5) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city TEXT NOT NULL,
    country_code CHAR(2) NOT NULL,
    count SMALLINT NOT NULL,
    remark TEXT NULL,
    amount NUMERIC(4,2) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE shipments (
    id SERIAL PRIMARY KEY,
    order_id UUID NOT NULL REFERENCES orders (id),
    count SMALLINT NOT NULL,
    shipping_company VARCHAR(15) NOT NULL,
    shipment_id VARCHAR(25) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    order_id UUID NOT NULL REFERENCES orders (id),
    amount NUMERIC(4,2) NOT NULL,
    payment_method VARCHAR(10) NOT NULL,
    transaction VARCHAR(20) NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
