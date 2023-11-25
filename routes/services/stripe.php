<?php
require_once "models/put.model.php";

require 'vendor/autoload.php';

// The library needs to be configured with your account's secret key.
// Ensure the key is kept out of any version control system you might be using.
$stripe = new \Stripe\StripeClient($_ENV['STRIPE_KEY']);


// This is your Stripe CLI webhook secret for testing your endpoint locally.
$endpoint_secret = $_ENV['STRIPE_ENPOINT_SECRET_TEST'];

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;



try {
  $event = \Stripe\Webhook::constructEvent(
    $payload, $sig_header, $endpoint_secret
  );

  http_response_code(200); // Responde inmediatamente a Stripe
  // Registrar que se ha recibido un evento
    $logMessage = "///// --- Webhook Event Received: " . $event->type . " at " . date('Y-m-d H:i:s') . "\n";
    $logFile = 'log.txt';
    file_put_contents($logFile, $logMessage, FILE_APPEND);


  $subscription = $event->data->object; // Datos de la suscripción
  $stripeCustomerId = $subscription->customer; // ID de cliente de Stripe

  // Determinar los datos a actualizar basado en el evento
  $dataToUpdate = [];
  switch ($event->type) {
    case 'customer.subscription.created':
      $stripeCustomerId = $event->data->object->customer;
      $dataToUpdate['subscription_type'] = "premium";
      $response = PutModel::putData('users', $dataToUpdate, $stripeCustomerId, 'stripe_customer_id');
      // Suponiendo que todo va bien
      $logMessage = "customer.subscription.created.\n" . $dataToUpdate['subscription_type'] . "\n" . $response;
      file_put_contents($logFile, $logMessage, FILE_APPEND);
      break;

    case 'invoice.payment_succeeded':
      $stripeCustomerId = $event->data->object->customer;
      // Verifica si la factura se ha pagado y actualiza según la lógica de tu aplicación

      if ($event->data->object->paid) {
        $dataToUpdate['subscription_type'] = "premium";
        $response = PutModel::putData('users', $dataToUpdate, $stripeCustomerId, 'stripe_customer_id');
      }
      $logMessage = "invoice.payment_succeeded.\n" . $dataToUpdate['subscription_type'] . "\n" . json_encode($response);
      file_put_contents($logFile, $logMessage, FILE_APPEND);
      break;
    case 'customer.subscription.deleted':
      $stripeCustomerId = $event->data->object->customer;
      // Actualiza la base de datos para reflejar la cancelación de la suscripción
      $dataToUpdate['subscription_type'] = "free";
      $response = PutModel::putData('users', $dataToUpdate, $stripeCustomerId, 'stripe_customer_id');
      // Llama a la función para actualizar la base de datos aquí
       $logMessage = "customer.subscription.deleted.\n" . $dataToUpdate['subscription_type'] . "\n" . $response;
      file_put_contents($logFile, $logMessage, FILE_APPEND);
      break;
    case 'customer.subscription.updated':
      $subscriptionStatus = $event->data->object->status;
      $currentPeriodEnd = $event->data->object->current_period_end; // Timestamp de la finalización del período actual

      if ($subscriptionStatus === 'canceled') {
          // La suscripción ha sido cancelada, pero sigue activa hasta el final del período actual
          $dataToUpdate['subscription_type'] = "free";
          $dataToUpdate['subscription_end_date'] = date('Y-m-d H:i:s', $currentPeriodEnd);
      } elseif ($subscriptionStatus === 'active') {
        $dataToUpdate['subscription_type'] = "premium";
        $dataToUpdate['subscription_end_date'] = date('Y-m-d H:i:s', $currentPeriodEnd);
      }
      else {
        $dataToUpdate['subscription_type'] = "free";
        $dataToUpdate['subscription_end_date'] = date('Y-m-d H:i:s', $currentPeriodEnd);
      }
       $response = PutModel::putData('users', $dataToUpdate, $stripeCustomerId, 'stripe_customer_id');
      // Llama a la función para actualizar la base de datos aquí
       $logMessage = "customer.subscription.deleted.\n" . $dataToUpdate['subscription_type'] . "\n" . $response;
      file_put_contents($logFile, $logMessage, FILE_APPEND);
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
    // Cualquier otro tipo de error
    http_response_code(500); // Error interno del servidor

    $response = [
        'status' => 'error',
        'message' => 'Error general al procesar el webhook',
        'error_details' => $e->getMessage()
    ];

    echo json_encode($response);
    exit();
}