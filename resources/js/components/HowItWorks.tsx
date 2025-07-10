import { CheckCircle2 } from "lucide-react";

const HowItWorks = () => {
  const steps = [
    {
      number: "01",
      title: "Register your account",
      description: "Create an account on our platform and complete the registration with your basic information."
    },
    {
      number: "02",
      title: "Connect WhatsApp Business",
      description: "Link your WhatsApp Business number following our guided integration process."
    },
    {
      number: "03",
      title: "Configure your preferences",
      description: "Define your products, prices, sales scripts, and customer service rules."
    },
    {
      number: "04",
      title: "Activate your agents",
      description: "Select the number of agents you need and start receiving sales."
    }
  ];

  return (
    <section id="how-it-works" className="section-padding">
      <div className="container-custom">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div>
            <h2 className="text-3xl md:text-4xl font-bold mb-6">
              How <span className="text-brandBlue">GetAlert</span> works
            </h2>
            <p className="text-lg text-gray-600 mb-10">
              Setting up our platform is easy and fast. In just 4 simple steps you can have professional sales agents serving your customers through WhatsApp.
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
                <p>No need for complex installations or technical knowledge</p>
              </div>
              <div className="flex items-start gap-3">
                <CheckCircle2 className="text-whatsapp mt-1 shrink-0" />
                <p>Technical support included throughout the entire process</p>
              </div>
              <div className="flex items-start gap-3">
                <CheckCircle2 className="text-whatsapp mt-1 shrink-0" />
                <p>Free initial training to maximize results</p>
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
