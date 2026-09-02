(function () {
    "use strict";

    let quoteTimer;

    function updateMessage(target, text, type) {
        target.textContent = text;
        target.classList.remove("is-success", "is-error");

        if (type) {
            target.classList.add(type);
        }
    }

    document.addEventListener("submit", async function (event) {
        const form = event.target;

        if (!form.classList.contains("pwt-booking-form")) {
            return;
        }

        event.preventDefault();

        const message = form.querySelector(".pwt-form-message");

        if (!message || !window.pwtFrontend) {
            return;
        }

        const packageField = form.querySelector("[name='package_id']");
        const dateField = form.querySelector("[name='travel_date']");
        const personsField = form.querySelector("[name='persons']");

        if (!packageField || !dateField || !personsField) {
            return;
        }

        const packageId = packageField.value;
        const travelDate = dateField.value;
        const persons = personsField.value;

        if (!packageId || !travelDate || !persons) {
            updateMessage(message, "<?php esc_html_e('Please fill in all fields.', 'wildtours-plugin'); ?>", "is-error");
            return;
        }

        updateMessage(message, "<?php esc_html_e('Checking availability...', 'wildtours-plugin'); ?>", "");

        const estimatePayload = new FormData();
        estimatePayload.append("action", "pwt_quote_booking");
        estimatePayload.append("nonce", form.querySelector("[name='nonce']")?.value ?? "");
        estimatePayload.append("package_id", packageId);
        estimatePayload.append("travel_date", travelDate);
        estimatePayload.append("persons", persons);

        try {
            const estimateResponse = await fetch(window.pwtFrontend.ajaxUrl, {
                method: "POST",
                body: estimatePayload,
                credentials: "same-origin"
            });

            const estimatePayloadData = await estimateResponse.json();

            if (!estimateResponse.ok || !estimatePayloadData.success) {
                const errorText = estimatePayloadData.data && estimatePayloadData.data.message
                    ? estimatePayloadData.data.message
                    : "<?php esc_html_e('Availability check failed.', 'wildtours-plugin'); ?>";

                updateMessage(message, errorText, "is-error");
                return;
            }

            const data = estimatePayloadData.data;
            const remaining = data.remaining ?? 0;
            const capacity = data.capacity ?? 0;

            if (remaining < persons) {
                updateMessage(message, "<?php esc_html_e('Insufficient inventory. Available: %1$s, Requested: %2$d'.replace('%1$s', String(capacity)).replace('%2$d', String(persons)), 'wildtours-plugin'); ?>", "is-error");
                return;
            }

            // Proceed to create the booking
            const bookingPayload = new FormData();
            bookingPayload.append("action", "pwt_booking");
            bookingPayload.append("nonce", form.querySelector("[name='nonce']")?.value ?? "");
            bookingPayload.append("package_id", packageId);
            bookingPayload.append("travel_date", travelDate);
            bookingPayload.append("persons", persons);

            const bookingResponse = await fetch(window.pwtFrontend.ajaxUrl, {
                method: "POST",
                body: bookingPayload,
                credentials: "same-origin"
            });

            const bookingResult = await bookingResponse.json();

            if (bookingResponse.ok && bookingResult.success) {
                // Booking created successfully - redirect to payment or thank you page
                window.location.href = "<?php echo esc_url(admin_url('admin.php?page=pwt-operations')); ?>?booking_id=" + bookingResult.data.booking_id;
            } else {
                const errorText = bookingResult.data && bookingResult.data.message
                    ? bookingResult.data.message
                    : "<?php esc_html_e('Booking creation failed.', 'wildtours-plugin'); ?>";
                updateMessage(message, errorText, "is-error");
            }
        } catch (error) {
            updateMessage(message, "<?php esc_html_e('Error checking availability.', 'wildtours-plugin'); ?>", "is-error");
            return;
        }
    });

    async function requestEstimate(form) {
        const packageField = form.querySelector("[name='package_id']");
        const dateField = form.querySelector("[name='travel_date']");
        const personsField = form.querySelector("[name='persons']");
        const estimateTarget = form.querySelector(".pwt-estimate");
        const nonceField = form.querySelector("[name='nonce']");

        if (!packageField || !dateField || !personsField || !estimateTarget || !nonceField || !window.pwtFrontend) {
            return;
        }

        const packageId = packageField.value;
        const travelDate = dateField.value;
        const persons = personsField.value;

        if (!packageId || !travelDate || !persons) {
            estimateTarget.textContent = "";
            return;
        }

        const payload = new FormData();
        payload.append("action", "pwt_quote_booking");
        payload.append("nonce", nonceField.value);
        payload.append("package_id", packageId);
        payload.append("travel_date", travelDate);
        payload.append("persons", persons);

        estimateTarget.textContent = "Estimating...";

        try {
            const response = await fetch(window.pwtFrontend.ajaxUrl, {
                method: "POST",
                body: payload,
                credentials: "same-origin"
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                estimateTarget.textContent = "";
                return;
            }

            const data = result.data;

            estimateTarget.textContent = data.formatted_total + " (" + data.season_label + ")";
        } catch (error) {
            estimateTarget.textContent = "";
        }
    }

    document.addEventListener("input", function (event) {
        const field = event.target;
        const form = field.closest(".pwt-booking-form");

        if (!form) {
            return;
        }

        if (!["package_id", "travel_date", "persons"].includes(field.name)) {
            return;
        }

        clearTimeout(quoteTimer);
        quoteTimer = setTimeout(function () {
            requestEstimate(form);
        }, 250);
    });
})();