document.addEventListener("DOMContentLoaded", function() {
    var uploadForm = document.getElementById("uploadForm");
    if (uploadForm) {
        uploadForm.onsubmit = function() {
            var loadingIndicator = document.getElementById("loadingIndicator");
            if (loadingIndicator) {
                loadingIndicator.style.display = "block";
                // Estimation code here
                var totalSize = 0;
                var files = document.querySelector('[name="images[]"]').files;
                for (var i = 0; i < files.length; i++) {
                    totalSize += files[i].size;
                }
                var uploadSpeed = 5 * 1024 * 1024; // 5 Mbps in bytes per second
                var estimatedTime = totalSize * 8 / uploadSpeed;
                loadingIndicator.innerText = "Uploading files, please wait... Estimated time: " + Math.round(estimatedTime) + " seconds.";
            }
            var formSubmitted = true; // Update this flag as needed
        };
    }

    window.onbeforeunload = function(event) {
        if (formSubmitted) { // Ensure this flag is accessible
            var message = "Your upload is still in progress. Are you sure you want to leave this page?";
            event.returnValue = message;
            return message;
        }
    };
});