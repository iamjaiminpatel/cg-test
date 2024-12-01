<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #FFC3A0; color: #333333; text-align: center;">

  <div style="max-width: 600px; margin: 50px auto; padding: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
    <!-- Logo -->
    <div style="margin: 20px auto;">
      <img src="{{ env('FRONT_APP_URL') }}logo.png" alt="Logo" style="max-width: 80px;">
    </div>

    <!-- Title -->
    <div style="font-size: 24px; font-weight: bold; color: #333333; margin: 20px 0;">
      Forgot Your Password?
    </div>

    <!-- Instruction Text -->
    <div style="font-size: 16px; line-height: 1.5; margin: 20px 0;">
      If you requested to reset your password, please use the code below:
    </div>

    <!-- Reset Code -->
    <div style="font-size: 28px; font-weight: bold; color: #333333; letter-spacing: 2px; padding: 10px; border-radius: 5px;">
        {{$token}}
    </div>

    <!-- Reset Button -->
    <a href="{{ env('FRONT_APP_URL') }}reset-password/{{$token}}" style="display: inline-block; margin: 20px 0; padding: 12px 20px; font-size: 16px; font-weight: bold; color: #ffffff; background-color: #ff7052; text-decoration: none; border-radius: 4px;">
      Reset Password
    </a>

    <!-- Footer Text -->
    <div style="font-size: 12px; color: #777777; margin-top: 20px;">
      This link will expire after 24 hours.
    </div>
  </div>

</body>
</html>

