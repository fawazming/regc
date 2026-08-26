# API Documentation | RIVO

## Endpoints

POST

/api/v1/payment/create

Create a payment session for a customer. Returns an authorization URL to guide the customer through the bank transfer.

Auth: `X-API-KEY`

Try it

Request

{"amount": 5000, "email": "customer@email.com", "redirect\_url": "https://merchant.com/success/order-42", "idempotency\_key": "optional-uuid"}

Response

{"status": true, "reference": "PGW202608010001", "amount": 5000, "payable\_amount": 5000.37, "authorization\_url": "https://gateway.com/pay/PGW202608010001", "redirect\_url": "https://merchant.com/success/order-42"}

-   • amount (required): value in Naira, > 0
-   • email (optional): customer email
-   • redirect\_url (optional): per-transaction URL the customer is redirected to after a successful payment
-   • idempotency\_key (optional): prevents duplicate sessions when retried

GET

/api/v1/payment/status/{reference}

Poll the lifecycle status of a payment session.

Auth: `X-API-KEY`

Try it

Response

{"status": "SUCCESS", "reference": "PGW202608010001", "amount": 5000, "paid\_amount": 5000.37}

-   • status: SUCCESS | PENDING | PROCESSING | EXPIRED

GET

/api/v1/payment/{reference}

Fetch the full payment session record.

Auth: `X-API-KEY`

Try it

Response

{"status": true, "session": {"reference": "PGW202608010001", "amount": "5000.00", "payable\_amount": "5000.37", ...}}

-   • Returns every field of the session including the receiving account and timestamps.

POST

/api/v1/payment/verify

Server-side verification of a payment session by reference.

Auth: `X-API-KEY`

Try it

Request

{"reference": "PGW202608010001"}

Response

{"status": "SUCCESS", "reference": "PGW202608010001", "amount": 5000, "paid\_amount": 5000.37, "email": "customer@email.com"}

-   • Use this from your backend to confirm a payment before crediting a customer.

GET

/api/v1/balance

Settlement balances per receiving account.

Auth: `X-API-KEY`

Try it

Response

{"status": true, "currency": "NGN", "total\_received": 5000.37, "accounts": \[{"bank\_name": "Zenith Bank", "account\_number": "0123456789", "total\_received": 5000.37, "settled\_count": 1}\]}

-   • Balances reflect completed and merchant-notified sessions.

### Authentication

Send your key in the `X-API-KEY` header.

X-API-KEY: pgsk\_live\_<prefix>.<secret>

Keys are stored as a one-way hash. If you lose a secret, regenerate it — the old one is invalidated.

### Error Codes

401

Missing or invalid X-API-KEY.

403

IP address is not allowlisted for this key.

404

Payment session not found.

409

Idempotency key was already used with a different payload.

422

Validation error (e.g. missing/invalid amount or email).

429

Rate limit exceeded. Retry after the Retry-After header.

503

No receiving account configured for the merchant.

### Webhooks

Successful payments are POSTed to your callback URL, signed with the `X-PGSP-Signature` header.

X-PGSP-Signature: t=<ts>,v1=<hmac-sha256>

Verify by recomputing HMAC-SHA256 over `timestamp + "." + body` using your webhook secret. See [Settings](https://rivo.rayyantech.com.ng/dashboard/settings).