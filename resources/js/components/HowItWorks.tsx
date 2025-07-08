
import { CheckCircle2 } from "lucide-react";

const HowItWorks = () => {
  const steps = [
    {
      number: "01",
      title: "Registra tu cuenta",
      description: "Crea una cuenta en nuestra plataforma y completa el registro con tus datos básicos."
    },
    {
      number: "02",
      title: "Conecta WhatsApp Business",
      description: "Vincula tu número de WhatsApp Business siguiendo nuestro proceso guiado de integración."
    },
    {
      number: "03",
      title: "Configura tus preferencias",
      description: "Define tus productos, precios, scripts de venta y reglas de atención al cliente."
    },
    {
      number: "04",
      title: "Activa tus agentes",
      description: "Selecciona el número de agentes que necesitas y comienza a recibir ventas."
    }
  ];

  return (
    <section id="como-funciona" className="section-padding">
      <div className="container-custom">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div>
            <h2 className="text-3xl md:text-4xl font-bold mb-6">
              Cómo funciona <span className="text-brandBlue">GetAlert</span>
            </h2>
            <p className="text-lg text-gray-600 mb-10">
              Configurar nuestra plataforma es fácil y rápido. En solo 4 simples pasos podrás tener agentes de ventas profesionales atendiendo a tus clientes a través de WhatsApp.
            </p>

            <div className="space-y-8">
              {steps.map((step, index) => (
                <div key={index} className="flex gap-4">
                  <div className="bg-brandBlue/10 text-brandBlue font-bold h-12 w-12 rounded-full flex items-center justify-center shrink-0">
                    {step.number}
                  </div>
                  <div>
                    <h3 className="text-xl font-bold mb-2">{step.title}</h3>
                    <p className="text-gray-600">{step.description}</p>
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-12 space-y-4">
              <div className="flex items-start gap-3">
                <CheckCircle2 className="text-whatsapp mt-1 shrink-0" />
                <p>Sin necesidad de instalaciones complejas ni conocimientos técnicos</p>
              </div>
              <div className="flex items-start gap-3">
                <CheckCircle2 className="text-whatsapp mt-1 shrink-0" />
                <p>Soporte técnico incluido durante todo el proceso</p>
              </div>
              <div className="flex items-start gap-3">
                <CheckCircle2 className="text-whatsapp mt-1 shrink-0" />
                <p>Capacitación inicial gratuita para maximizar resultados</p>
              </div>
            </div>
          </div>

          <div className="relative">
            <div className="bg-gradient-to-br from-brandBlue/10 to-whatsapp/10 rounded-2xl overflow-hidden shadow-xl p-8">
              <img
                src="https://images.unsplash.com/photo-1596524430615-b46475ddff6e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                alt="WhatsApp Business Dashboard"
                className="rounded-xl shadow-lg border border-white/20"
              />
            </div>

            <div className="absolute -z-10 top-10 -right-10 h-[300px] w-[300px] bg-brandBlue/5 rounded-full blur-3xl"></div>
            <div className="absolute -z-10 bottom-10 -left-10 h-[300px] w-[300px] bg-whatsapp/5 rounded-full blur-3xl"></div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default HowItWorks;
