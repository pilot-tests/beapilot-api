<?php
require_once "models/put.model.php";

require 'vendor/autoload.php';

// The library needs to be configured with your account's secret key.
// Ensure the key is kept out of any version control system you might be using.
$stripe = new \Stripe\StripeClient($_ENV['STRIPE_KEY']);


// This is your Stripe CLI webhook secret for testing your endpoint locally.
$endpoint_secret = 'whsec_db72c72807a59688c4fb0d2cc8345e91aa70217b29b85df16cdf563d17e5fead';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;



try {
  $event = \Stripe\Webhook::constructEvent(
    $payload, $sig_header, $endpoint_secret
  );

  http_response_code(200); // Responde inmediatamente a Stripe


  $subscription = $event->data->object; // Datos de la suscripción
  $stripeCustomerId = $subscription->customer; // ID de cliente de Stripe

  // Determinar los datos a actualizar basado en el evento
  $dataToUpdate = [];
  switch ($event->type) {
    case 'invoice.payment_succeeded':
      $stripeCustomerId = $event->data->object->customer;
      // Verifica si la factura se ha pagado y actualiza según la lógica de tu aplicación

      if ($event->data->object->paid) {
        $dataToUpdate['subscription_type'] = "premium";
        $response = PutModel::putData('users', $dataToUpdate, $stripeCustomerId, 'stripe_customer_id');
      }
      break;
    case 'customer.subscription.deleted':
      $stripeCustomerId = $event->data->object->customer;
      // Actualiza la base de datos para reflejar la cancelación de la suscripción
      $dataToUpdate['subscription_type'] = "free";
      // Llama a la función para actualizar la base de datos aquí
      break;
    default:
      // Cualquier otro evento se considera una suscripción "free"
      $dataToUpdate['subscription_type'] = "free";
      // Llama a la función para actualizar la base de datos aquí si es necesario
      break;
  }
  $response = [
    'status' => 'success',
    'message' => 'Webhook processed correctly',
];

} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400); // Firma incorrecta

    $response = [
        'status' => 'error',
        'message' => 'Error de verificación de firma',
        'error_details' => $e->getMessage()
    ];

    echo json_encode($response);
    exit();
} catch (\Exception $e) {
    http_response_code(400); // Firma incorrecta

    $response = [
        'status' => 'error',
        'message' => 'Error de verificación de firma',
        'error_details' => $e->getMessage()
    ];

    echo json_encode($response);
    exit();
}