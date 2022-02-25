const enableDisableButtons = (status) => {
    ['submit','minimal','complete'].forEach((button)=>{
        document.getElementById(button).disabled = !status;
    });
}

const submit = () => {
    
    enableDisableButtons(false);
    document.getElementById('result').innerHTML = 'Loading';

    fetch('./create-data-down', {
        method: 'post',
        headers: {
            "Accept": "application/json"
        },
        body: document.getElementById('json').value
    })
    .then(response => response.text())
    .then(response => {
        enableDisableButtons(true);
        document.getElementById('result').innerHTML = 
            '<pre>' + JSON.stringify(JSON.parse(response), null, 4) + '</pre>';
    });
}

const load = (type) => {
    
    enableDisableButtons(false);
    document.getElementById('json').value = 'Loading';

    fetch('./configuration/' + type, {
        method: 'get',
        headers: {
            "Accept": "application/json"
        }
    })
    .then(response => response.text())
    .then(response => {
        enableDisableButtons(true);
        document.getElementById('json').value = 
            JSON.stringify(JSON.parse(response), null, 4);
    });
}

window.onload = () => {
    document.getElementById('submit').addEventListener("click", (e)=>{
        e.preventDefault();
        submit();
    });

    document.getElementById('minimal').addEventListener("click", (e)=>{
        e.preventDefault();
        load('minimal');
    });

    document.getElementById('complete').addEventListener("click", (e)=>{
        e.preventDefault();
        load('complete');
    });

    document.getElementById('json').addEventListener('keydown', function(e) {
        if (e.key == 'Tab') {
            e.preventDefault();
            var start = this.selectionStart;
            var end = this.selectionEnd;
        
            this.value = this.value.substring(0, start) +
            "\t" + this.value.substring(end);
        
            this.selectionStart =
            this.selectionEnd = start + 1;
        }
    });

    if (!document.getElementById('json').value)
    {
        load('minimal');
    }
}