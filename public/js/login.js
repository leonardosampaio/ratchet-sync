const login = () => {
    fetch('./login', {
        method: 'post',
        headers: {
            "Accept": "application/json"
        },
        body: JSON.stringify({
            'login': document.getElementById('login').value,
            'password': document.getElementById('password').value
        })
    })
    .then(response => response.json())
    .then(response => {

        if (response.success)
        {
            window.location = window.location.origin + '/home';
        }
        else {
            alert('Error: ' +
                (response.errors ? response.errors.join('<br>') : 'unknown'));
        }
    });
}

window.onload = () => {
    document.querySelectorAll("input").forEach((input)=>{
        input.addEventListener("keydown", (e) =>{
            if (e.keyCode == 13){
                e.preventDefault();
                login();
             }
        });
    });

    document.getElementById('submit').addEventListener("click", (e)=>{
        e.preventDefault();
        login();
    });
}