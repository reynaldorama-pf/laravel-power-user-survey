<!doctype html>
  <html lang="{{ str_replace('_','-', app()->getLocale()) }}">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rate Limit Exceeded</title>
  </head>

  <body>
    <main style="max-width: 860px; margin: 40px auto; padding: 0 16px; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
      <h1 style="font-size:28px; margin:0 0 10px 0;">Rate Limit Exceeded</h1>
      <p style="margin:0; color:#444;">You have exceeded the amount of requests allowed in the allotted time limit.</p>
    </main>

    @powerUserSurveyPayload(['mode' => $mode, 'redirectTo' => $redirectTo])

    <script src="{{ asset('build/js/power-user-survey.js') }}"></script>
  </body>
</html>