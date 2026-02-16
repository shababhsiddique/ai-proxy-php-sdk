## Bitmesh API Reference

Basic reference for the public HTTP API exposed by this service.

- **Base URL**: `https://<your-domain>`  
- **Content Type**: `application/json` for all JSON endpoints  
- **Authentication**: Protected endpoints require a valid API key via the OAuthOneLegged flow (see middleware stack in code). The `/imgrslt/{id}` route is public.

---

## Health & Utility

### `GET /test`

- **Description**: Simple authenticated health-check to verify OAuth/API-key middleware and rate limiting are working.
- **Auth**: Required (same stack as main API).
- **Request Body**: _None_
- **Query Parameters**: _None_
- **Response**
  - **Status**: `200 OK`
  - **Body**:

    ```json
    {
      "message": "you are the man"
    }
    ```

---

## Chat Completions

### `POST /chat`  
### `POST /v1/chat`

- **Description**: Proxy chat completion endpoint, forwards to the configured AI provider based on model and API key configuration.
- **Auth**: Required (OAuthOneLegged + API key + rate limiting).
- **Request Headers**
  - **`Content-Type`**: `application/json`
  - **Auth headers**: As required by OAuthOneLegged / API key middleware (see middleware implementation).
- **Request Body**
  - **`model`**:  
    - Type: `string`  
    - Required if the API key does **not** have a default model (`ai_model_id === null`).  
    - **Prohibited** if the API key has a fixed default model.
  - **`messages`** (required)
    - Type: `array` of objects
    - Constraints: minimum 1 element
    - Items:
      - **`role`** (required): `string`, one of: `system`, `user`, `assistant`, `tool`
      - **`content`** (required): `string`
  - **Optional parameters**
    - **`max_tokens`**: `integer` ≥ 1  
    - **`temperature`**: `number` between 0 and 2  
    - **`repetition_penalty`**: `number` ≥ 0  
    - **`frequency_penalty`**: `number` between -2 and 2  
    - **`presence_penalty`**: `number` between -2 and 2  
    - **`test`**: `boolean` – if true, charges are not applied (`live` flag is false).
- **Responses**
  - **200 OK**: Successful response from provider
    - Body: Raw provider response, including at least:
      - **`id`**: provider-side request ID
      - **`usage`** (shape depends on provider)
        - May include `total_tokens`, `prompt_tokens`, `completion_tokens` or camelCase equivalents.
      - **`choices`** / similar content field, depending on provider.
  - **4xx**: Validation or provider errors
    - `422 Unprocessable Entity` – Laravel validation error, body:
      - `error`: `"Validation failed"`
      - `errors`: object keyed by field name.
  - **5xx**: Provider or internal failure
    - `500 Internal Server Error`
    - Body:
      - `error`: `"Internal server error"` or provider error message.

---

## Image Generation

### `POST /image`  
### `POST /v1/image`

- **Description**: Generate images via underlying AI providers and return metadata and proxied URLs.
- **Auth**: Required (OAuthOneLegged + API key + rate limiting).
- **Request Headers**
  - `Content-Type: application/json`
- **Request Body**
  - **`model`**:
    - Type: `string`
    - Required if API key has no default model; prohibited if API key has a fixed model.
  - **`prompt`** (required): `string`
  - **Optional parameters**
    - **`width`**: `integer` ≥ 1  
    - **`height`**: `integer` ≥ 1  
    - **`steps`**: `integer` ≥ 1  
    - **`seed`**: `integer`  
    - **`n`**: `integer` ≥ 1 – number of images to generate
- **Responses**
  - **200 OK**
    - Body: Provider image generation response, with all image URLs rewritten to your proxy:
      - `data`: array of objects
        - `url`: `string` – URL on this service, e.g. `https://<your-domain>/imgrslt/{id}`
  - **422 Unprocessable Entity**
    - Body:
      - `error`: `"Validation failed"`
      - `errors`: detailed field errors.
  - **4xx/5xx**: Provider or internal errors, body contains an `error` field mirroring provider response.

---

## Video Generation

### `POST /video`  
### `POST /v1/video`

- **Description**: Generate videos via the underlying AI provider.
- **Auth**: Required (OAuthOneLegged + API key + rate limiting).
- **Request Headers**
  - `Content-Type: application/json`
- **Request Body**
  - **`model`**:
    - Type: `string`
    - Required if API key has no default model; prohibited if API key has a fixed model.
  - **`prompt`** (required): `string`, length 1–32000 characters.
  - **Optional parameters**
    - **`width`**: `integer` ≥ 1  
    - **`height`**: `integer` ≥ 1  
    - **`seconds`**: `string` – duration (per provider API)  
    - **`fps`**: `integer` ≥ 1  
    - **`steps`**: `integer` between 10 and 50  
    - **`seed`**: `integer`  
    - **`guidance_scale`**: `number` ≥ 0  
    - **`output_format`**: `string`, one of `MP4`, `WEBM`  
    - **`output_quality`**: `integer` ≥ 1  
    - **`negative_prompt`**: `string`  
    - **`frame_images`**: `array`
      - Items:
        - `input_image` (required with `frame_images`): `string`
        - `frame` (required with `frame_images`): `string` – frame index or `"last"`
    - **`reference_images`**: `array` of `string`
- **Responses**
  - **200 OK**
    - Body: Raw provider result (shape depends on TogetherAI / provider). May contain:
      - `id`: video job ID (used by `GET /video/{id}`).
      - `outputs` / `data` sections with video URLs and metadata.
  - **422 Unprocessable Entity**
    - Standard Laravel validation error format (`error`, `errors`).
  - **4xx/5xx**: Provider or internal errors with an `error` field.

---

## Video Status & Retrieval

### `GET /video/{id}`  
### `GET /v1/video/{id}`

- **Description**: Fetch video generation job details from the provider and adjust pricing metadata.
- **Auth**: Required (same middleware stack as generation endpoints).
- **Path Parameters**
  - **`id`**: `string` – provider video job ID.
- **Response**
  - **200 OK**
    - Body: Provider video status payload, with:
      - `id`: job ID
      - `status`: e.g. `queued`, `running`, `completed`
      - `outputs`: may contain:
        - `video_url`: video URL (rewritten to use your `/imgrslt/{id}` proxy if it is a Together short URL)
        - `cost`: numeric cost fields (used internally for accounting).
  - **5xx / 4xx**: Error details with `error` message.

---

## Public Image Proxy

### `GET /imgrslt/{id}`

- **Description**: Public image-serving endpoint that proxies images from TogetherAI short URLs.
- **Auth**: Not required.
- **Path Parameters**
  - **`id`**: `string` – short image identifier (alphanumeric).
- **Response**
  - **200 OK**
    - Binary image content; `Content-Type` header determined from upstream response (defaults to `image/png`).
  - **404 Not Found**
    - Text body: `"Resource not found"`
  - **500 Internal Server Error**
    - Text body: `"Error fetching image"`

---

## Notes

- **Rate Limiting**: Endpoints are protected by multiple throttling middlewares (per-IP, per-API-key, and per-endpoint where configured).
- **Billing**: The service calculates internal cost and user charges based on model configuration (`AiModel`, `getBestProvider()`); these values are stored in `ApiRequest` records and not all are exposed in the HTTP JSON response.
- **Model Selection**: If an API key is bound to a specific model, clients must **not** send the `model` field; otherwise, calls will fail validation.
