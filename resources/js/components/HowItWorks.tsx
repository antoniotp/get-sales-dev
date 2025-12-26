
import { CheckCircle2, Lightbulb, Target } from "lucide-react";
import dashboardImage from "@/assets/getsales-dashboard.png";

const HowItWorks = () => {
  const steps = [
    {
      number: "01",
      title: "Registra tu cuenta",
      description: "Crea tu cuenta en GetSales y completa tu perfil con la información básica de tu negocio."
    },
    {
      number: "02",
      title: "Conecta tu WhatsApp Business",
      description: "Vincula tu número siguiendo nuestro proceso guiado. Estarás listo para operar en minutos."
    },
    {
      number: "03",
      title: "Configura tus preferencias",
      description: "Define tus productos, precios, mensajes y reglas de atención. Personaliza la experiencia según tu marca."
    },
    {
      number: "04",
      title: "Activa tus agentes",
      description: "Enciende tus agentes de IA y empieza a recibir ventas de forma automática."
    }
  ];

  return (
    <section id="como-funciona" className="section-padding">
      <div className="container-custom">
        {/* Header - Full width */}
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold mb-6">
            🧩 Cómo funciona <span className="text-brandRed">GetSales</span>
          </h2>
          <p className="text-lg text-gray-600 max-w-3xl mx-auto">
            Configurar GetSales es rápido y sencillo. En sólo 4 pasos tendrás agentes inteligentes atendiendo y vendiendo por WhatsApp.
          </p>
        </div>

        {/* Steps and Image - Two columns */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-12">
          <div className="space-y-8">
            {steps.map((step, index) => (
              <div key={index} className="flex gap-4">
                <div className="bg-brandRed/10 text-brandRed font-bold h-12 w-12 rounded-full flex items-center justify-center shrink-0">
                  {step.number}
                </div>
                <div>
                  <h3 className="text-xl font-bold mb-2">{step.title}</h3>
                  <p className="text-gray-600">{step.description}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="relative">
            <div className="bg-gradient-to-br from-brandRed/10 to-whatsapp/10 rounded-2xl overflow-hidden shadow-xl p-4">
              <img 
                src="https://0cf46a50-ecd1-461a-82a3-b86625fd2e0d.lovable.app/assets/getsales-dashboard-CbQWz752.png"
                alt="GetSales Dashboard" 
                className="rounded-xl shadow-lg"
              />
            </div>
            
            <div className="absolute -z-10 top-10 -right-10 h-[300px] w-[300px] bg-brandRed/5 rounded-full blur-3xl"></div>
            <div className="absolute -z-10 bottom-10 -left-10 h-[300px] w-[300px] bg-whatsapp/5 rounded-full blur-3xl"></div>
          </div>
        </div>

        {/* Benefits - Full width */}
        <div className="flex flex-wrap justify-center gap-8">
          <div className="flex items-start gap-3">
            <CheckCircle2 className="text-whatsapp mt-1 shrink-0" />
            <p>Sin instalaciones complejas ni conocimientos técnicos</p>
          </div>
          <div className="flex items-start gap-3">
            <Lightbulb className="text-brandRed mt-1 shrink-0" />
            <p>Soporte técnico incluido durante todo el proceso</p>
          </div>
          <div className="flex items-start gap-3">
            <Target className="text-brandRed mt-1 shrink-0" />
            <p>Capacitación inicial gratuita para maximizar tus resultados</p>
          </div>
        </div>
      </div>
    </section>
  );
};

export default HowItWorks;
