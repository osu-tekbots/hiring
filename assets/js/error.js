function sendDiagnostics(issue) {
    if(issue.toLowerCase().trim() == 'script error.')
        return; // Don't need to hear about external issues we can't fix
    let data = {
        action: 'errorEmail',
        body: issue,
    };

    api.post('/email.php', data).then(res => {
        snackbar('Uncaught error! Sent diagnostics', 'error');
    }).catch(err => {
        console.error('Failed to send error email: ' + err.message);
    });
}

// Credit: https://stackoverflow.com/a/11219680/21684315
function getBrowserInfo() {
    var nAgt = navigator.userAgent;
    var browserName  = navigator.appName;
    var fullVersion  = ''+parseFloat(navigator.appVersion); 
    var nameOffset,verOffset,ix;

    // In Opera, the true version is after "OPR" or after "Version"
    if ((verOffset=nAgt.indexOf("OPR"))!=-1) {
        browserName = "Opera";
        fullVersion = nAgt.substring(verOffset+4);
    if ((verOffset=nAgt.indexOf("Version"))!=-1) 
        fullVersion = nAgt.substring(verOffset+8);
    }
    // In MS Edge, the true version is after "Edg" in userAgent
    else if ((verOffset=nAgt.indexOf("Edg"))!=-1) {
        browserName = "Microsoft Edge";
        fullVersion = nAgt.substring(verOffset+4);
    }
    // In MSIE, the true version is after "MSIE" in userAgent
    else if ((verOffset=nAgt.indexOf("MSIE"))!=-1) {
        browserName = "Microsoft Internet Explorer";
        fullVersion = nAgt.substring(verOffset+5);
    }
    // In Chrome, the true version is after "Chrome" 
    else if ((verOffset=nAgt.indexOf("Chrome"))!=-1) {
        browserName = "Chrome";
        fullVersion = nAgt.substring(verOffset+7);
    }
    // In Safari, the true version is after "Safari" or after "Version" 
    else if ((verOffset=nAgt.indexOf("Safari"))!=-1) {
        browserName = "Safari";
        fullVersion = nAgt.substring(verOffset+7);
        if ((verOffset=nAgt.indexOf("Version"))!=-1) 
            fullVersion = nAgt.substring(verOffset+8);
    }
    // In Firefox, the true version is after "Firefox" 
    else if ((verOffset=nAgt.indexOf("Firefox"))!=-1) {
        browserName = "Firefox";
        fullVersion = nAgt.substring(verOffset+8);
    }
    // In most other browsers, "name/version" is at the end of userAgent 
    else if ((nameOffset=nAgt.lastIndexOf(' ')+1) < (verOffset=nAgt.lastIndexOf('/'))) {
        browserName = nAgt.substring(nameOffset,verOffset);
        fullVersion = nAgt.substring(verOffset+1);
        if (browserName.toLowerCase()==browserName.toUpperCase()) {
            browserName = navigator.appName;
        }
    }
    // trim the fullVersion string at semicolon/space if present
    if ((ix=fullVersion.indexOf(";"))!=-1)
        fullVersion=fullVersion.substring(0,ix);
    if ((ix=fullVersion.indexOf(" "))!=-1)
        fullVersion=fullVersion.substring(0,ix);

    return 'Browser: '+browserName+' '+fullVersion+'\n'+'UserAgent: '+navigator.userAgent+'\n'
}

window.onunhandledrejection = event => {
    const issue = `UNHANDLED PROMISE REJECTION: \n${event.reason}\nWebpage: ${window.location}`;
    sendDiagnostics(issue);
};

window.onerror = function(message, source, lineNumber, colno, error) {
    const issue = `UNHANDLED ERROR:\n${message} \nSource: ${source}\nLocation: line ${lineNumber}, col ${colno}\nWebpage: ${window.location}\n${getBrowserInfo()}`;
    sendDiagnostics(issue);
};