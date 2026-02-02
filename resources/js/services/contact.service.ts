type ContactFormPayload = {
  name: string;
  email: string;
  phone?: string;
  message: string;
  honeypot_field?: string;
  'g-recaptcha-response': string;
};

export async function sendContactForm(data: ContactFormPayload) {
    const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

  const response = await fetch('/contact', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken!,
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    let errorMessage = 'Error sending message';

    try {
      const errorData = await response.json();
      errorMessage = errorData.message ?? errorMessage;
    } catch {
      // If it’s not JSON, we use the default message.
    }

    throw new Error(errorMessage);
  }

  return response.json();
}
