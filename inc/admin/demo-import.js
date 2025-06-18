// bp-demo-import.js
jQuery(document).ready(function ($) {
  const steps = BPDemoSteps.steps;
  let currentStep = 0;

  const logEl = $('#bp-demo-log');
  const barEl = $('.bp-demo-progress .bar');
  const modal = $('#bp-demo-modal');
  const loader = $('#bp-demo-loader');
  const finalBtns = $('#bp-demo-final-buttons');
  const startBtn = $('#bp-start-import');

  function showMessage(message, isError = false) {
    logEl.text(message);
    logEl.css('color', isError ? 'red' : '');
  }

  function updateProgress() {
    const percent = ((currentStep + 1) / steps.length) * 100;
    barEl.css('width', percent + '%');
  }

  function runNextStep() {
    if (currentStep >= steps.length) {
      showMessage('✅ Import complete!');
      loader.hide();
      finalBtns.show();
      return;
    }

    const step = steps[currentStep];
    showMessage(`⏳ ${step.replace(/_/g, ' ')}...`);

    $.ajax({
      url: BPDemoSteps.ajax_url,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'bp_demo_import_step',
        step: step
      }
    })
    .done(function (response) {
      if (response.success) {
        showMessage(`✅ ${response.data.message}`);
      } else {
        const msg = response.data?.message || 'Unknown error';
        showMessage(`⚠️ Failed: ${msg}`, true);
      }
      updateProgress();
      currentStep++;
      setTimeout(runNextStep, 800); // smoother transition
    })
    .fail(function (xhr, status, error) {
      console.error('AJAX failed:', error);
      showMessage(`❌ AJAX error: ${error}`, true);
      updateProgress();
      currentStep++;
      setTimeout(runNextStep, 800);
    });
  }

  $('#start-demo-import').on('click', function () {
    modal.fadeIn();
    logEl.text('Ready to import...');
    barEl.css('width', '0');
    loader.hide();
    finalBtns.hide();
  });

  startBtn.on('click', function () {
    loader.show();
    startBtn.hide();
    currentStep = 0;
    runNextStep();
  });
});
