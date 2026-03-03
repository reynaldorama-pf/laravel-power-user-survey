@if(!empty($pusPayload['enabled']) && !empty($pusPayload['apiKey']))
  <div id="pus-modal-root" style="display:none"></div>

  <script>
    window.PowerUserSurvey = window.PowerUserSurvey || {};
    window.PowerUserSurvey.config = @json($pusPayload);
  </script>
@endif
