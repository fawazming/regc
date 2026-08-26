<?php
/**
 * REGC — Configuration
 * Copy to config.php and fill in your values.
 */

define('APP_NAME', 'Royale Experience Global Concept');
define('APP_TAGLINE', 'Premium Foodstuff Delivery');
define('APP_BASE_URL', 'http://localhost/regc');

/* -------------------------
   SUPABASE (database)
   -------------------------
   Find these in your Supabase project:
   Project Settings -> API.
   - URL:      https://<project-ref>.supabase.co
   - anon key:  JWT published key
   - service_role key:  server-only, keep secret (use this one in PHP)
*/
define('SUPABASE_URL', 'https://YOUR-PROJECT.supabase.co');
define('SUPABASE_ANON_KEY', 'YOUR-ANON-KEY');
define('SUPABASE_SERVICE_KEY', 'YOUR-SERVICE-ROLE-KEY'); // server-side only

/* Default admin password used ONLY until you set one in the Admin -> Settings page.
   The stored hash in the database takes priority. */
define('DEFAULT_ADMIN_PASSWORD', 'change-me-now');

/* Timezone */
date_default_timezone_set('Africa/Lagos');