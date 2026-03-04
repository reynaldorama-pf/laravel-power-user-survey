@if(!empty($pusPayload['enabled']))
  <div id="pus-modal-root" style="display:none"></div>
  <script>
    window.PowerUserSurvey = window.PowerUserSurvey || {};
    window.PowerUserSurvey.config = @json($pusPayload);
  </script>
@endif
