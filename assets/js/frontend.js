(($) => {
    $(document).ready(() => {
        // Initialize progress bar
        function initProgressBar() {
            const $container = $(".wc-progress-bar-container");

            if ($container.length) {
                // Check if we need to update via AJAX
                if (typeof wcProgressBar !== "undefined") {
                    updateProgressBar($container);

                    // Update when cart is updated
                    $(document.body).on("updated_cart_totals updated_wc_div", () => {
                        updateProgressBar($container);
                    });
                }
            }
        }

        // Update progress bar via AJAX
        function updateProgressBar($container) {
            $.ajax({
                url: wcProgressBar.ajax_url,
                type: "POST",
                data: {
                    action: "get_progress_bar_data",
                    nonce: wcProgressBar.nonce,
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        const $fill = $container.find(".wc-progress-bar-fill");
                        const $text = $container.find(".wc-progress-bar-text");

                        // Update text
                        $text.html(data.text);

                        // Update progress bar width
                        if ($container.data("transition") === "1") {
                            $fill.css("width", `${data.progress}%`);
                        } else {
                            $fill.stop().css("width", `${data.progress}%`);
                        }

                        // Update ARIA attributes
                        $fill.attr("aria-valuenow", data.progress);
                    }
                },
            });
        }

        // Initialize
        initProgressBar();
    });
})(jQuery);
