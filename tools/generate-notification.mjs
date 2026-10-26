// Original STT two-note notification chime. No samples or third-party audio.
// Regenerate from repository root: node tools/generate-notification.mjs
import {mkdir,writeFile} from 'node:fs/promises';
const rate=44100,duration=.68,count=Math.round(rate*duration),samples=new Float64Array(count);
const notes=[{at:0,hz:783.9909,level:.8},{at:.13,hz:1046.5023,level:.65}];
let peak=0;
for(let i=0;i<count;i++){
 const time=i/rate;let value=0;
 for(const n of notes){const t=time-n.at;if(t<0)continue;
  const attack=Math.min(1,t/.012),decay=Math.exp(-t/ .115),fade=Math.min(1,(duration-time)/.045);
  const bell=Math.sin(2*Math.PI*n.hz*t)+.1*Math.sin(2*Math.PI*n.hz*2*t)+.025*Math.sin(2*Math.PI*n.hz*3*t);
  value+=n.level*attack*decay*fade*bell;
 }
 samples[i]=value;peak=Math.max(peak,Math.abs(value));
}
const bytes=Buffer.alloc(44+count*2);
bytes.write('RIFF',0);bytes.writeUInt32LE(bytes.length-8,4);bytes.write('WAVEfmt ',8);bytes.writeUInt32LE(16,16);
bytes.writeUInt16LE(1,20);bytes.writeUInt16LE(1,22);bytes.writeUInt32LE(rate,24);bytes.writeUInt32LE(rate*2,28);bytes.writeUInt16LE(2,32);bytes.writeUInt16LE(16,34);bytes.write('data',36);bytes.writeUInt32LE(count*2,40);
for(let i=0;i<count;i++)bytes.writeInt16LE(Math.round(samples[i]/peak*.5*32767),44+i*2);
const folder=new URL('../Upload/jscripts/sttshoutbox/',import.meta.url);
await mkdir(folder,{recursive:true});await writeFile(new URL('notify.wav',folder),bytes);
console.log(JSON.stringify({file:'Upload/jscripts/sttshoutbox/notify.wav',bytes:bytes.length,seconds:duration,rate,channels:1,peakDb:-6.02}));
