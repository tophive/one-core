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


// TEmPLATES IMPORT MODAL

jQuery(document).ready(function($) {
  let selectedTemplateId = null;

  $('.import-button').on('click', function () {
    selectedTemplateId = $(this).data('template-id');

    // Check if Elementor is installed
    if (!BPDemoSteps.elementor_installed) {
      $('.elementor-warning').show();
    } else {
      $('.elementor-warning').hide();
    }
    const $btn = $('#confirm-import-button');
    const $text = $btn.find('.text');
    const $loader = $btn.find('.loader');

    $loader.hide();
    $text.text('Import');

    $('#import-page-selector').val('');
    $('#new-page-title').val('');
    $('#template-import-modal').fadeIn();

    $('#import-success-message').hide();
      $('#imported-page-link').attr('href', '');

    // Hide all form-related inputs and buttons (to avoid confusion)
    $('#import-page-selector').show();
    $('#new-page-title').show();
    $('#confirm-import-button').show();
    $('label[for="import-page-selector"]').show();
    $('#import-page-selector').prev('p').show(); // hides "Select existing page:" <p>
    $('#new-page-title').prev('p').show();       // hides "Or create a new page:" <p>
  });

  $('#cancel-import-button').on('click', function () {
    $('#template-import-modal').fadeOut();
  });

  $('#confirm-import-button').on('click', function () {
    const $btn = $(this);
    const $text = $btn.find('.text');
    const $loader = $btn.find('.loader');
  
    const pageId = $('#import-page-selector').val();
    const newTitle = $('#new-page-title').val();
  
    // Show loader
    $text.text('Importing...');
    $loader.show();
  
    $.post(BPDemoSteps.ajax_url, {
      action: 'bp_demo_import_step',
      step: 'import_elementor_template',
      template_id: selectedTemplateId,
      page_id: pageId,
      new_title: newTitle
    }, function(response) {
      // Hide loader
      
      if (response.success) {
        $loader.hide();
        $text.text('Import');
        $('#import-success-message').show();
        $('#imported-page-link').attr('href', response.data.page_url);
  
        $('#import-page-selector, #new-page-title, #confirm-import-button').hide();
        $('label[for="import-page-selector"]').hide();
        $('#import-page-selector').prev('p').hide();
        $('#new-page-title').prev('p').hide();
      } else {
        alert('Import failed: ' + (response.data?.message || 'Unknown error'));
      }
    });
  });
  
});
