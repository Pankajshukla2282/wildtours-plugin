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

        updateMessage(message, window.pwtFrontend.messages.submitting, "");

        const formData = new FormData(form);

        try {
            const response = await fetch(window.pwtFrontend.ajaxUrl, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                const errorText = payload && payload.data && payload.data.message
                    ? payload.data.message
                    : window.pwtFrontend.messages.error;

                updateMessage(message, errorText, "is-error");
                return;
            }

            const successText = payload.data && payload.data.message
                ? payload.data.message
                : window.pwtFrontend.messages.success;

            const paymentUrl = payload.data && payload.data.payment_url
                ? payload.data.payment_url
                : "";

            const advanceAmount = payload.data && payload.data.payment_advance_amount
                ? payload.data.payment_advance_amount
                : 0;

            form.reset();
            updateMessage(message, successText, "is-success");

            if (paymentUrl) {
                const link = document.createElement("a");
                link.href = paymentUrl;
                link.target = "_blank";
                link.rel = "noopener noreferrer";
                link.textContent = advanceAmount
                    ? "Complete advance payment"
                    : "Open payment page";

                message.appendChild(document.createTextNode(" "));
                message.appendChild(link);
            }
        } catch (error) {
            updateMessage(message, window.pwtFrontend.messages.error, "is-error");
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
