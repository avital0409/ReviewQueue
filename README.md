# Review Queue — Full-Stack Take-Home Assignment

A high-fidelity, production-grade moderation oversight application built with **Vue 3** (Composition API) on the frontend and **Laravel 11** on the backend, utilizing a localized **SQLite** database for persistence.

---

## 🚀 How to Run the Project

### 🐳 1. Running via Docker (Lightweight & Instant / Recommended)

To build and run the full-stack application instantly in seconds with **absolutely zero manual setup** (Docker will automatically handle creating `.env` from `.env.example`, generating a secure `APP_KEY`, compiling frontend assets, creating the SQLite database, and running migrations/seeders):

1. **Build and Start Container**:
   ```bash
   docker compose up --build
   ```

   > [!TIP]
   > **AI Integration Setup**: The application utilizes the **Google Gemini API** (`gemini-1.5-flash`) for sub-second, high-performance email draft generation. 
   > 
   > Simply obtain a completely free API key from **[Google AI Studio](https://aistudio.google.com)** and paste it as `GEMINI_API_KEY=your_key` in your `.env` file. If no API key is supplied, the application automatically detects it and gracefully degrades to premium static email templates so the dashboard is fully functional out of the box.

2. **Access ModHub**:
   Open `http://localhost:8000` in your web browser. Everything is fully pre-configured, database migrations are automatically run, and the seed data is populated!

---

### 🔧 2. Manual Setup (Alternative Setup)

If you prefer to run the application components individually on your host machine:

#### 📦 Prerequisites
- **PHP** (8.2 or 8.3)
- **Composer**
- **Node.js** (v18 or higher) & **NPM**

#### ⚙️ Backend Setup (Laravel)

1. **Install Dependencies**:
   ```bash
   composer install
   ```

2. **Configure Environment Variables**:
   Copy `.env.example` to `.env`. The database path defaults to an auto-created SQLite file (`database/database.sqlite`).
   ```bash
   cp .env.example .env
   ```

3. **Initialize Database**:
   Run database migrations and seeders to populate initial pending moderation items:
   ```bash
   php artisan migrate --seed
   ```

4. **Serve the API Gateway**:
   ```bash
   php artisan serve
   ```
   The backend API will be available at `http://127.0.0.1:8000`.

#### 🎨 Frontend Setup (Vue 3 / Vite)

1. **Install NPM Packages**:
   ```bash
   npm install
   ```

2. **Start Dev Server**:
   ```bash
   npm run dev
   ```
   The interactive moderation panel will be available at `http://localhost:5173`.

---

## ⚙️ Environment Configuration Variables (`.env`)

The application defines a few specific configuration parameters inside the `.env` file to customize the database connection and the AI orchestration layer:

| Key | Default Value | Description |
|---|---|---|
| `GEMINI_API_KEY` | | The Google Gemini API key to query for dynamic, high-speed rejection/ban email suggestions. (Obtain a free key from Google AI Studio). |
| `DB_CONNECTION` | `sqlite` | The primary Eloquent database connection driver. |
| `DB_DATABASE` | `database/database.sqlite` | The path to the local SQLite database file. |

---

## 🛠️ Key Architectural Decisions

### 📊 1. Data Model Choices
The schema prioritizes clean separation of concerns and submitter reputational history:
* **`moderation_items`**: Tracks the primary content stream including its status (`pending`, `approved`, `rejected`, `blocked`), heuristic `risk_score`, auto-generated AI classification suggestions, notes, and specific triggered flags.
* **`users` / `submitters`**: Submitter accounts are tracked by their email. Rather than viewing submissions in isolation, the system tallies lifetime counts (approved, rejected, blocked) and increments a **Strike Count**. If a submitter accumulates 3 strikes (rejections), they are automatically flagged as `is_banned` and their content is marked for immediate critical block.

### 🔌 2. API Design Choices
The backend exposes a highly RESTful, JSON-structured API layer:
* `GET /api/moderation-items`: Fetches items in the queue with active support for state filters (`pending`, `approved`, `rejected`, `blocked`), query search matching submitters or keywords, and dynamic sorting (Newest, Highest Risk).
* `POST /api/moderation-items`: Submits new items to the queue, triggering the automated heuristic scan synchronously.
* `PUT /api/moderation-items/{id}`: Processes a moderation resolution (approving, rejecting, or blocking) and records reviewer notes.
* `POST /api/moderation-items/{id}/rejection-email-draft`: Synthesizes an automated, contextual rejection draft.
* `POST /api/users/{email}/ban/toggle`: Manually toggles a submitter's global ban status and suspends active content.

### 💾 3. Persistence Choice
* **SQLite**: Selected for maximum ease of review and zero-config portability. It runs entirely out of a single file (`database/database.sqlite`), removing all database server initialization blockers while fully supporting robust SQL transactions, indexes, and Laravel's Eloquent ORM.
* *For Production*: For a scaled, high-throughput service, this would seamlessly migrate to **PostgreSQL** by updating the DB driver in the `.env` file, allowing concurrent read/write scaling.

---

## 🔮 Core Automated Moderation Heuristics

The system includes a two-tiered synchronous and asynchronous moderation engine:
1. **Rule-Based Heuristic Scans (Synchronous)**:
   Upon submission, the text is audited for urgent phrases, financial keywords, suspicious links, excessive casing, and global blacklist states to assign a custom `risk_score` (0–100) and trigger automated tags.
2. **AI-Driven Sentiment & Auto-Drafting (Google Gemini API Integration)**:
   Integrates with Google's **Gemini API** (`gemini-1.5-flash`) to perform semantic intent analysis and dynamically draft polite, highly contextual, and custom rejection or suspension warning emails:
   * **Context-Aware Generation**: When a reviewer rejects an item or bans an account, the backend compiles the submitter's email, the original violating content snippet, and the reviewer's specific custom reason into a structured instruction prompt.
   * **Custom Prompt Engineering**: Instructs the LLM to write in a constructive, professional tone, explaining the guidelines violation, and strictly forbids bracketed placeholders (e.g. `[User Name]`, `[Date]`), ensuring the drafts are 100% complete and ready to send.
   * **High-Fidelity Offline Fallback**: If the `GEMINI_API_KEY` is not provided or the API is unreachable, the app automatically detects the fallback state and gracefully drops back to structured, professional static drafts to preserve the moderator's workflow.

---

## 💭 Assumptions Made
* **No Authentication**: Assumed that the review dashboard operates in a trusted admin/moderator dashboard context (auth-free for home assignment simplicity).
* **Identity by Email**: Assumed that submitters are uniquely and strictly identified by their email address rather than an underlying system User ID.
* **Proactive Spam Prevention**: Assumed that one of the core goals of the system is to protect the community by proactively blocking spammers and repeat policy violators from hosting content.
* **Automated Pending & Future Sweep**: Assumed that when a submitter is banned (either automatically after Strike 3 or manually by a moderator), all of their active pending queue submissions should be immediately swept and transitioned to a `'blocked'` state, and any future incoming submissions from their email address are automatically intercepted at the API gateway, marked as `'blocked'` with a `100` risk score, and flagged as `banned_author`.
* **Single-Moderator Flow**: Assumed that only one reviewer accesses the queue at any given time, deferring complex collaborative lockout mechanisms (ticket locking) to scale scopes.
* **Local Mail Logging**: Configured mail drivers to write outbound confirmation emails directly to the Laravel logs (`MAIL_MAILER=log`) for easy manual evaluation.
* **Auto-Ban Logic Infallibility**: Assumed that reaching exactly 3 rejections represents a definitive ban threshold, while providing a manual ban-toggle switch for senior moderation overrides.
* **AI Fallbacks**: In environments where a `GEMINI_API_KEY` is not provided in `.env`, the service automatically detects the state and gracefully falls back to structured rule-based template generation to preserve user workflows.

---

## ⚖️ Tradeoffs & Optimization

### 🚀 Optimized For
* **Premium UX/UI**: Implemented modern UI design, dynamic transitions, custom SVG circular indicators, status badge systems, and slide-in notification states.
* **Clean Code Modularity**: Decoupled the monolithic `ReviewDashboard.vue` into specialized, highly declarative single-file components:
  * `StatusBadge.vue` — Centralized status labels.
  * `ToastNotification.vue` — Slide-in success/error toast stack.
  * `UserListItem.vue` — Submitter list items.
  * `ModerationItemCard.vue` — Queue card tickets.
  * `StrikeScorecard.vue` — Submitter reputation strike bubbles.
  * `HeuristicFlagList.vue` — Triggered tags formatting.
  * `RiskScoreDial.vue` — Interactive circular SVG risk gauge.

### 🛑 Intentionally Deferred
* **User Authentication & Role-Based Access Control (RBAC)**: Since this is an internal admin/moderator dashboard environment, I deferred login screens, session management, and role separations (e.g., Senior Administrator vs. Junior Moderator overrides) to keep evaluator onboarding completely friction-free.
* **Websockets / Realtime Sync**: Opted for optimistic frontend updates and clean manual list refetch triggers rather than introducing complex WebSocket channels.
* **Multi-tenant Moderator Locking**: In a collaborative workplace, the system would lock tickets dynamically to prevent double-moderation.
* **Outbound Mail Infrastructure & Queues**: Configured email outputs to route directly to Laravel logs (`MAIL_MAILER=log`) for simple zero-setup local validation, deferring paid SMTP/SES provider setups and Redis background queue worker integrations.
* **Interactive Rules Creator Panel**: Heuristic violation parameters (financial triggers, word weights, blacklist strings) are defined programmatically. I deferred building an admin control panel UI that allows senior moderators to dynamically add, edit, or remove heuristic check rules on the fly.

### 🧠 Free Cloud AI (Google Gemini) vs. Self-Hosted / Paid LLMs
A major architectural choice was using Google's free-tier Gemini API (`gemini-1.5-flash`) instead of local self-hosted options (like Ollama) or other paid cloud APIs (like OpenAI or Anthropic):
* **Why I Used Gemini**: I optimized for **blazing-fast response speeds (sub-second generation), zero local resource consumption, and zero developer cost**. It completely avoids requiring the evaluator to download massive 4GB local models or run intensive host engines, while offering the most robust and responsive user experience possible.
* **The Resilient Fallback**: If no API key is configured or Google servers are unreachable, the app's internal service caught checks automatically redirect to provide high-fidelity static drafts, making onboarding completely bulletproof.

---

## 🧪 Testing Strategy

The project features a **fully green, double-sided automated test suite**:

### 1. Backend Testing (PHPUnit)
Located in `tests/Feature`:
* **`RejectionModerationTest.php`**: Assures moderation status transitions, note persistence, and strike increments.
* **`UserBanManagementTest.php`**: Assures that 3 rejections trigger automated submitter bans, and verifying manual ban lift workflows.
* **`RejectionEmailTest.php`**: Confirms email dispatching parameters.
* *Run Backend Tests*:
  ```bash
  php artisan test
  ```

### 2. Frontend Testing (Vitest + JSDOM)
Located in `resources/js/tests`:
* **`SubmitItemModal.spec.js`**: Confirms form data validation, modal emissions, and axios post triggers.
* **`RejectionEmailModal.spec.js`**: Confirms that draft generation payloads are bound cleanly to draft inputs.
* *Run Frontend Tests*:
  ```bash
  npm run test:vue:run
  ```
