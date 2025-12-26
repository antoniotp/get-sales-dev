import { Users, MessageSquare, Bot, TrendingUp, Zap, Clock, Rocket } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";

const Features = () => {
  const featuresData = [
    {
      icon: <Bot className="h-10 w-10 text-brandRed" />,
      title: "IA aplicada a tu negocio",
      description: "Tu negocio ahora usará IA para conectar con clientes a través de WhatsApp y cerrar más ventas."
    },
    {
      icon: <MessageSquare className="h-10 w-10 text-whatsapp" />,
      title: "Integración con WhatsApp en 5 minutos",
      description: "Conectas tu cuenta de WhatsApp Business y comienzas a operar en minutos."
    },
    {
      icon: <Clock className="h-10 w-10 text-brandRed" />,
      title: "Atención 24/7",
      description: "La IA está siempre disponible. Tus clientes recibirán respuesta en cualquier momento del día, en cualquier idioma, sin errores, y mejorando su experiencia y fidelidad."
    },
    {
      icon: <Users className="h-10 w-10 text-whatsapp" />,
      title: "Múltiples Usuarios Simultáneos",
      description: "Varios miembros de tu equipo pueden responder desde la misma cuenta de WhatsApp, sin límites de acceso."
    },
    {
      icon: <TrendingUp className="h-10 w-10 text-brandRed" />,
      title: "Ventas Automáticas y Recordatorios Inteligentes",
      description: "La IA detecta oportunidades, hace seguimiento y ofrece tus productos o servicios en el momento justo."
    },
    {
      icon: <Zap className="h-10 w-10 text-whatsapp" />,
      title: "Fácil de Usar y Escalar",
      description: "Diseñado para crecer contigo: empieza con un número, y agrega más agentes o canales (Messenger, Instagram, etc.) cuando lo necesites."
    }
  ];

  return (
    <section id="features" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <div className="flex items-center justify-center gap-2 mb-4">
            <h2 className="text-3xl md:text-4xl font-bold">
              Todo lo que necesitas para <span className="text-brandRed">vender más</span>
            </h2>
            <Rocket className="h-8 w-8 text-brandRed" />
          </div>
          <p className="text-lg text-gray-600">
            Convertimos tu Whatsapp en una Máquina Automatizada de Ventas usando Inteligencia Artificial. Más ventas. En automático.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {featuresData.map((feature, index) => (
            <Card key={index} className="border-none shadow-lg hover:shadow-xl transition-shadow overflow-hidden bg-white">
              <CardContent className="">
                <div className="mb-6">{feature.icon}</div>
                <h3 className="text-xl font-bold mb-2 dark:text-gray-950">{feature.title}</h3>
                <p className="text-gray-600">{feature.description}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Features;
