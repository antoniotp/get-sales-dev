
import { Users, MessageSquare, BarChart3, UserCheck, Clock, ShieldCheck } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";

const Features = () => {
  const featuresData = [
    {
      icon: <Users className="h-10 w-10 text-brandBlue" />,
      title: "Agentes Profesionales",
      description: "Conecta con agentes especializados en ventas a través de WhatsApp que representarán tu negocio."
    },
    {
      icon: <MessageSquare className="h-10 w-10 text-whatsapp" />,
      title: "Integración con WhatsApp Business",
      description: "Conecta fácilmente tu cuenta de WhatsApp Business y comienza a operar en minutos."
    },
    {
      icon: <Clock className="h-10 w-10 text-brandBlue" />,
      title: "Atención 24/7",
      description: "Tus clientes recibirán atención en cualquier momento del día, mejorando su experiencia."
    },
    {
      icon: <BarChart3 className="h-10 w-10 text-whatsapp" />,
      title: "Analíticas Detalladas",
      description: "Métricas en tiempo real sobre conversiones, tiempo de respuesta y satisfacción del cliente."
    },
    {
      icon: <UserCheck className="h-10 w-10 text-brandBlue" />,
      title: "Personalización de Mensajes",
      description: "Adapta los mensajes y el tono de comunicación según tu marca y tipo de clientes."
    },
    {
      icon: <ShieldCheck className="h-10 w-10 text-whatsapp" />,
      title: "Seguridad Garantizada",
      description: "Todas las conversaciones son seguras y cumplen con las normativas de protección de datos."
    }
  ];

  return (
    <section id="funciones" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4 dark:text-gray-950">
            Todo lo que necesitas para <span className="text-brandBlue">potenciar tus ventas</span>
          </h2>
          <p className="text-lg text-gray-600">
            Nuestro sistema te proporciona todas las herramientas necesarias para convertir conversaciones de WhatsApp en ventas exitosas.
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
