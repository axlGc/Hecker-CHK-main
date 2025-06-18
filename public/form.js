function tokenizeCard(publishableKey, formId) {
    console.log('Iniciando tokenización con Stripe, formId:', formId);
    const stripe = Stripe(publishableKey);
    console.log('Stripe inicializado');
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        hidePostalCode: true,
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                '::placeholder': {
                    color: '#aab7c4'
                }
            }
        }
    });
    cardElement.mount('#card-element');
    console.log('Elemento de tarjeta montado');

    // Manejar el envío del formulario
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const errorMessage = document.getElementById('error-message');

    submitButton.addEventListener('click', function(event) {
        event.preventDefault();
        submitButton.disabled = true;
        errorMessage.textContent = '';
        console.log('Botón de enviar clicado');

        stripe.createToken(cardElement).then(function(result) {
            console.log('Resultado de createToken:', result);
            if (result.error) {
                console.error('Error al crear token:', result.error.message);
                errorMessage.textContent = result.error.message;
                submitButton.disabled = false;
                return;
            }
            console.log('Token creado:', result.token.id);

            fetch('/Hecker-CHK-main/submit-token', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: result.token.id, formId: formId })
            }).then(function(res) {
                console.log('Respuesta de submit-token:', res);
                return res.json();
            }).then(function(data) {
                console.log('Datos de submit-token:', data);
                if (data.success) {
                    document.body.innerHTML = '<p>Token submitted successfully. You can close this page.</p>';
                } else {
                    errorMessage.textContent = data.error || 'Error submitting token';
                    submitButton.disabled = false;
                }
            }).catch(function(err) {
                console.error('Error en fetch:', err);
                errorMessage.textContent = 'Error de red: ' + err.message;
                submitButton.disabled = false;
            });
        }).catch(function(err) {
            console.error('Error en createToken:', err);
            errorMessage.textContent = 'Error de Stripe: ' + err.message;
            submitButton.disabled = false;
        });
    });
}