jQuery(document).ready(function($) {
    $("#edit-developer-btn").on("click", function() {
        $("#developer-readonly").hide();
        $("#developer-form-container").show();
    });

    $("#cancel-edit-btn").on("click", function() {
        $("#developer-form-container").hide();
        $("#developer-readonly").show();
    });

    $("#copy-address-btn").on("click", function() {
        $("#sprzed_wojewodztwo").val($("#siedz_wojewodztwo").val());
        $("#sprzed_powiat").val($("#siedz_powiat").val());
        $("#sprzed_gmina").val($("#siedz_gmina").val());
        $("#sprzed_miejscowosc").val($("#siedz_miejscowosc").val());
        $("#sprzed_ulica").val($("#siedz_ulica").val());
        $("#sprzed_nr").val($("#siedz_nr").val());
        $("#sprzed_lokal").val($("#siedz_lokal").val());
        $("#sprzed_kod").val($("#siedz_kod").val());
    });

    $("#developer-form").on("submit", function(e) {
        e.preventDefault();

        var $button = $(this).find("input[type=submit]");
        var originalValue = $button.val();
        $button.val("⏳ Zapisywanie...").prop("disabled", true);

        const formData = $("#developer-form").serialize() + "&action=jawneceny_save_developer";

        $.post(supplierDataPageData.ajaxurl, formData, function(response) {
            if (response.success) {
                alert("✅ " + response.data);
                location.reload();
            } else {
                alert("❌ " + response.data);
            }
            $button.val(originalValue).prop("disabled", false);
        }).fail(function(xhr, status, error) {
            alert("❌ Błąd połączenia: " + error);
            $button.val(originalValue).prop("disabled", false);
        });
    });
});