<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
         :root {
            --red: #ff2424;
            --red-shadow: #b60606;
        }

        body {
            font-family: Arial, Helvetica, sans-serif
        }

        .button {

        background: linear-gradient(to bottom, #C552E8, #b03bd3);
        padding: 12px;
        color: white !important;
        text-decoration: none;
        font-family: 'Franklin Gothic', 'Arial Narrow', Arial, sans-serif;
        border-radius:30px;
        font-size: 20px;
        /* line-height: 1.5; */
        display: inline-block;
        }

        .button:hover {
            opacity: 0.8;
        }

        .text-dark{
            color: rgb(92, 92, 92);
        }

        .text-primary{
            color:#C552E8;
            /* text-shadow: 1px 1px grey; */
        }
        .text-red-l{
            color:rgb(255, 122, 122);
        }
        p{
            font-size: 14px;
        }
    </style>
</head>

<body>
    <br>
    <br>
    <div class="container2">

        <div class="card"
            style="">
            <div style="text-align:center;">

            {{-- <img src="{{ $message->embed(public_path() . '/images/logotipos/klikler-email.jpg') }}" /> --}}
                <br>
                <h3 class="text-primary" >Solicitud de retiro de {{$data['type']}} por el usuario: {{$data['alias']}} de {{env('APP_NAME')}}</h3>
                <h4 class="text-dark"> <b>Fecha:</b> {{$data['date']}} </h4>
                <br>
                
                <a class="button" href="{{ env('ENDPOINT_FRONT') }}">  IR A {{env('APP_NAME')}} </a>
                <br>
                <br>
                <br>
               <p class="text-dark">
                <small >
                    Esto es un mensaje automático, no responda este mensaje.
                </small>
               </p>
            </div>
        </div>

    </div>
</body>

</html>
