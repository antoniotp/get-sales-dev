
import { CheckCircle2, Lightbulb, Target } from "lucide-react";
import { useTranslation } from "react-i18next";

type HowItWorksStep = {
  number: string;
  title: string;
  description: string;
};

const HowItWorks = () => {
  const { t } = useTranslation("home");

  const steps = t("howItWorks.steps", {
    returnObjects: true
  }) as HowItWorksStep[];

  const benefits = t("howItWorks.benefits", {
    returnObjects: true
  }) as string[];


  return (
    <section id="como-funciona" className="section-padding">
      <div className="container-custom">
        {/* Header - Full width */}
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold mb-6">
            {t("howItWorks.title.prefix")} <span className="text-brandRed">{t("howItWorks.title.highlight")}</span>
          </h2>
          <p className="text-lg text-gray-900 max-w-3xl mx-auto">
            {t("howItWorks.description")}
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
                  <p className="text-gray-900">{step.description}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="relative">
            <div className="bg-gradient-to-br from-brandRed/10 to-whatsapp/10 rounded-2xl overflow-hidden shadow-xl p-4">
              <img 
                src="/images/getsales-dashboard.png"
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
            <p className="font-medium">{benefits[0]}</p>
          </div>
          <div className="flex items-start gap-3">
            <Lightbulb className="text-brandRed mt-1 shrink-0" />
            <p className="font-medium">{benefits[1]}</p>
          </div>
          <div className="flex items-start gap-3">
            <Target className="text-brandRed mt-1 shrink-0" />
            <p className="font-medium">{benefits[2]}</p>
          </div>
        </div>
      </div>
    </section>
  );
};

export default HowItWorks;
