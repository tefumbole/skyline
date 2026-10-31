<style>
    .letter-compose-section {
        border: 1px solid #e5eaf3;
        border-radius: 12px;
        background: #fff;
        padding: 16px;
        margin-bottom: 18px;
        box-shadow: 0 2px 10px rgba(11, 63, 144, 0.05);
    }
    .letter-compose-section .section-label {
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0b3f90;
        background: #f4f8ff;
        border: 1px solid #d7e4fb;
        border-radius: 999px;
        padding: 4px 12px;
        margin-bottom: 10px;
    }
    .letter-compose-section .section-label.body {
        background: #fff8e8;
        border-color: #f4dfb0;
        color: #8a6400;
    }
    .letter-compose-section .section-label.footer {
        background: #fff1f1;
        border-color: #f2c6c6;
        color: #8a1f1f;
    }
    .letter-editor-wrap .tox-tinymce {
        border-radius: 10px !important;
        border-color: #d7e4fb !important;
    }
    .letter-editor-compact {
        padding: 8px 10px;
        margin-bottom: 10px;
    }
    .letter-editor-compact .section-label { margin-bottom: 6px; }
    .letter-editor-compact .tox-tinymce {
        height: 64px !important;
        min-height: 64px !important;
    }
    .letter-create .card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(12, 24, 48, .08);
    }
    .letter-create .card-header {
        background: #0c1830;
        color: #fff;
        border: 0;
    }
    .letter-create .card-header h4 { color: #fff; margin: 0; }
    .letter-create .card-body { background: #f3f6fb; }
    .letter-create .form-control,
    .letter-create .bootstrap-select > .dropdown-toggle {
        min-height: 42px;
        border-radius: 10px;
        border-color: #d7deea;
    }
    .letter-create label { color: #3d4d66; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
    .letter-create #submit-btn {
        min-height: 46px;
        padding: 0 22px;
        border: 0;
        border-radius: 12px;
        background: #e1b368;
        color: #0c1830;
        font-weight: 700;
    }
    .letter-create .letter-compose-section {
        border-color: #e4eaf3;
        box-shadow: none;
    }
    .letter-recipient-panel {
        background: #f8fbff;
        border: 1px dashed #0b3f90;
        border-radius: 12px;
        padding: 14px;
    }
    #product-form .is-invalid,
    #product-form .form-control.is-invalid,
    #product-form select.is-invalid,
    #product-form .bootstrap-select > .dropdown-toggle.is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.2);
    }
    #product-form .letter-panel-invalid {
        border: 2px solid #dc3545 !important;
        background: #fff5f5 !important;
        box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.12);
    }
    #product-form .letter-field-error-msg {
        color: #dc3545;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }
    #product-form label.letter-label-invalid,
    #product-form .section-label.letter-label-invalid {
        color: #dc3545 !important;
    }
</style>
