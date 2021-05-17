$(function() {
    window.addEventListener("keydown", function (event) {
        if (event.defaultPrevented) {
            return; // Do nothing if the event was already processed
        }
        var url;
        switch (event.key) {
            case "Left": // IE/Edge specific value
            case "ArrowLeft":
                url = $("#navigatePrev").attr('href');
                if (url) {
                    window.location.href = url;
                }
            break;
            case "Right": // IE/Edge specific value
            case "ArrowRight":
                url = $("#navigateNext").attr('href');
            break;
            default:
            return; // Quit when this doesn't handle the key event.
        }
        if (url) {
            window.location.href = url;
        }
        // Cancel the default action to avoid it being handled twice
        event.preventDefault();
        }, true);
});
