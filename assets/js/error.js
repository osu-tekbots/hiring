function sendDiagnostics(issue) {
    let data = {
        action: 'errorEmail',
        body: issue,
    };

    api.post('/email.php', data).then(res => {
        if(message.toLowerCase().trim() == 'script error' || message.toLowerCase().trim() == 'script error.') {
            snackbar('Cross-domain script error! Sent diagnostics', 'warn');
        } else {
            snackbar('Uncaught error! Sent diagnostics', 'error');
        }
    }).catch(err => {
        console.error('Failed to send error email: ' + err.message);
    });
}

window.onunhandledrejection = event => {
    const issue = `UNHANDLED PROMISE REJECTION: \n${event.reason}\nWebpage: ${window.location}`;
    sendDiagnostics(issue);
};

window.onerror = function(message, source, lineNumber, colno, error) {
    const issue = `UNHANDLED ERROR:\n${message} \nSource: ${source}\nLocation: line ${lineNumber}, col ${colno}\nWebpage: ${window.location}`;
    sendDiagnostics(issue);
};