document.addEventListener(
    'DOMContentLoaded',
    function(){

        const chat = document.getElementById('aicw-chat');
        const toggle = document.getElementById('aicw-toggle');
        const close = document.getElementById('aicw-close');

        const send = document.getElementById('aicw-send');
        const input = document.getElementById('aicw-message');
        const messages = document.getElementById('aicw-messages');


        toggle.addEventListener(
            'click',
            function(){

                chat.classList.remove('closed');

            }
        );


        close.addEventListener(
            'click',
            function(){

                chat.classList.add('closed');

            }
        );


        send.addEventListener(
            'click',
            function(){

                let text = input.value.trim();


                if(text === ''){
                    return;
                }


                let message = document.createElement('div');

                message.className = 'aicw-user-message';

                message.textContent = text;


                messages.appendChild(message);


                input.value='';


                messages.scrollTop = messages.scrollHeight;


                /*
                    Future API call:

                    fetch('/wp-json/ai-chatbot/v1/message',{
                        method:'POST',
                        headers:{
                            'Content-Type':'application/json'
                        },
                        body:JSON.stringify({
                            message:text
                        })
                    })

                */


            }
        );


    }
);