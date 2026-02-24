// printHelper.js
document.addEventListener('DOMContentLoaded', function() {
    // Auto-print function with proper sizing for Zebra ZD230
    function printLabel() {
        let style = `
            @page {
                size: 5cm 1.5cm;  // Set exact dimensions for Zebra ZD230
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .tag-container {
                width: 5cm;
                height: 1.5cm;
                padding: 0;
                margin: 0;
            }
        `;

        let styleSheet = document.createElement('style');
        styleSheet.innerText = style;
        document.head.appendChild(styleSheet);

        // Configure print settings
        let printConfig = {
            silent: true,  // Don't show printer dialog
            deviceName: 'zebra',  // Your Zebra printer name
            margins: {
                marginType: 'custom',
                top: 0,
                bottom: 0,
                left: 0,
                right: 0
            }
        };

        // Print the document
        window.print();
        
        // Remove the temporary style sheet
        styleSheet.remove();
    }

    // Call printLabel when page loads
    if (!window.location.search.includes('noprint')) {
        printLabel();
    }
});