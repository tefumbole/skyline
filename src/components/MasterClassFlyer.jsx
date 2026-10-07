import React from 'react';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { 
  Cpu, Wifi, Radio, Server, Mic2, MonitorPlay, Lightbulb, ShieldCheck, Zap
} from 'lucide-react';

function MasterClassFlyer({ onRegister }) {
  const modules = [
    { name: "Live Audio Engineering", icon: <Mic2 className="w-5 h-5" /> },
    { name: "Lights Engineering", icon: <Lightbulb className="w-5 h-5" /> },
    { name: "LED Screen Engineering", icon: <MonitorPlay className="w-5 h-5" /> },
    { name: "Cloud Computing", icon: <Server className="w-5 h-5" /> },
    { name: "VoIP Technologies", icon: <Radio className="w-5 h-5" /> },
    { name: "Fiber Optics", icon: <Zap className="w-5 h-5" /> },
    { name: "Cisco Enterprise Solutions", icon: <ShieldCheck className="w-5 h-5" /> },
    { name: "Wireless Technologies", icon: <Wifi className="w-5 h-5" /> },
  ];

  return (
    <div className="relative w-full overflow-hidden rounded-2xl shadow-2xl bg-[#0a192f] border border-blue-900/50">
      {/* Background Effects */}
      <div className="absolute inset-0 z-0 opacity-20">
        <div className="absolute top-0 left-0 w-full h-full bg-[url('https://horizons-cdn.hostinger.com/81ef3422-3855-479e-bfe8-28a4ceb0df39/a2f0aecc8dc9aabce8623f6022fa1775.png')] bg-cover bg-center mix-blend-overlay"></div>
        <div className="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-navy via-transparent to-blue-900"></div>
      </div>
      
      {/* Circuit Board Pattern Overlay */}
      <div className="absolute inset-0 z-0 opacity-10 pointer-events-none" style={{ backgroundImage: 'radial-gradient(circle, #4169e1 1px, transparent 1px)', backgroundSize: '30px 30px' }}></div>

      <div className="relative z-10 p-8 md:p-12 lg:p-16 flex flex-col md:flex-row items-center justify-between gap-12">
        
        {/* Left Content */}
        <div className="flex-1 space-y-6 text-center md:text-left">
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            className="inline-flex items-center space-x-2 px-4 py-2 rounded-full bg-blue-900/40 border border-blue-500/30 text-blue-200 text-sm font-semibold tracking-wider uppercase backdrop-blur-sm"
          >
            <Cpu className="w-4 h-4 text-gold" />
            <span>Advanced Certification</span>
          </motion.div>

          <motion.h2 
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.1 }}
            className="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight"
          >
            Master Class <br />
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-gold to-yellow-200">
              Training Program
            </span>
          </motion.h2>

          <motion.p 
             initial={{ opacity: 0, y: 20 }}
             whileInView={{ opacity: 1, y: 0 }}
             viewport={{ once: true }}
             transition={{ delay: 0.2 }}
             className="text-xl text-blue-100 font-light"
          >
            July 2026 • Kigali, Rwanda
          </motion.p>
          
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.3 }}
            className="pt-4"
          >
            <Button 
              onClick={onRegister}
              className="bg-gradient-to-r from-gold to-yellow-600 hover:from-yellow-500 hover:to-yellow-700 text-navy font-bold text-lg px-8 py-6 rounded-lg shadow-lg shadow-gold/20 hover:shadow-gold/40 transition-all hover:scale-105"
            >
              Register Now
            </Button>
            <p className="mt-3 text-sm text-gray-400">$300 USD per module</p>
          </motion.div>
        </div>

        {/* Right Content - Modules Grid */}
        <motion.div 
          initial={{ opacity: 0, scale: 0.9 }}
          whileInView={{ opacity: 1, scale: 1 }}
          viewport={{ once: true }}
          transition={{ delay: 0.4 }}
          className="flex-1 w-full max-w-md"
        >
          <div className="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-6 shadow-2xl">
            <h3 className="text-white font-bold text-xl mb-6 border-b border-white/10 pb-4">Available Modules</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {modules.map((mod, idx) => (
                <div key={idx} className="flex items-center space-x-3 text-gray-200 group hover:text-white transition-colors">
                  <div className="p-2 rounded-lg bg-blue-900/50 text-gold group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    {mod.icon}
                  </div>
                  <span className="text-sm font-medium">{mod.name}</span>
                </div>
              ))}
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  );
}

export default MasterClassFlyer;