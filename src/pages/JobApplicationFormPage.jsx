import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { checkJobAvailability, incrementApplicantCount, getApplicationStats } from '@/services/jobsService';
import { createApplication, checkExistingApplication } from '@/services/applicationsService';
import { uploadCV } from '@/services/cvUploadService';
import { generateReferenceNumber } from '@/services/referenceNumberService';
import { sendApplicationConfirmation, sendApplicationReceivedAdmin } from '@/services/emailService';
import { formatSalary } from '@/services/currencyService';
import CountdownTimerBox from '@/components/CountdownTimerBox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, UploadCloud, ChevronLeft, AlertCircle, CheckCircle, Users } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import CountryCodeSelect from '@/components/CountryCodeSelect';

const JobApplicationFormPage = () => {
  const { jobId } = useParams();
  const navigate = useNavigate();
  const { toast } = useToast();
  const { user } = useAuth();
  
  const [job, setJob] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const [remainingApps, setRemainingApps] = useState(null);
  const [loadingMessage, setLoadingMessage] = useState("Submitting Application...");
  
  const [formData, setFormData] = useState({
    candidate_name: '',
    email: '',
    phone: '',
    countryCode: '+250',
    description: ''
  });
  const [cvFile, setCvFile] = useState(null);

  const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  // Simple check that it has at least some digits
  const PHONE_REGEX = /^[0-9\s-]{5,}$/;

  useEffect(() => {
    // Pre-fill form from Auth User Data
    if (user) {
        setFormData(prev => ({
            ...prev,
            candidate_name: user.user_metadata?.full_name || 'Engr. Mbole',
            email: user.email || prev.email,
            phone: user.user_metadata?.phone || prev.phone
        }));
    } else {
        // Default value for unauthenticated visitors
        setFormData(prev => ({
            ...prev,
            candidate_name: ''
        }));
    }
  }, [user]);

  useEffect(() => {
    const fetchAndValidateJob = async () => {
      try {
        const { available, reason, job: jobData } = await checkJobAvailability(jobId);
        
        if (!available) {
            setError(reason);
        } else {
            setJob(jobData);
            // Fetch remaining apps
            const stats = await getApplicationStats(jobId);
            setRemainingApps(stats.remainingSpots);
        }
      } catch (error) {
        toast({ title: "Error", description: "Could not load job details.", variant: "destructive" });
        navigate('/apply-now');
      } finally {
        setLoading(false);
      }
    };
    if (jobId) fetchAndValidateJob();
  }, [jobId, navigate, toast]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleCountryChange = (value) => {
    setFormData(prev => ({ ...prev, countryCode: value }));
  };

  const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0];
      if (file.type !== 'application/pdf') {
          toast({ title: "Invalid File", description: "Only PDF files are allowed.", variant: "destructive" });
          e.target.value = null;
          return;
      }
      if (file.size > 5 * 1024 * 1024) {
          toast({ title: "File too large", description: "Max file size is 5MB.", variant: "destructive" });
          e.target.value = null;
          return;
      }
      setCvFile(file);
    }
  };

  const validateForm = () => {
      if (!formData.candidate_name.trim()) return "Full Name is required";
      if (!formData.email.trim() || !EMAIL_REGEX.test(formData.email)) return "Please enter a valid email address";
      if (!formData.phone.trim() || !PHONE_REGEX.test(formData.phone)) return "Please enter a valid phone number";
      if (!cvFile) return "Please upload your CV in PDF format";
      return null;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const validationError = validateForm();
    if (validationError) {
        toast({ title: "Validation Error", description: validationError, variant: "destructive" });
        return;
    }

    setSubmitting(true);
    
    try {
        setLoadingMessage("Checking availability...");
        const { available, reason } = await checkJobAvailability(jobId);
        if (!available) throw new Error(`Cannot submit: ${reason}`);

        const exists = await checkExistingApplication(jobId, formData.email);
        if (exists) throw new Error("You have already applied for this position with this email.");

        let userId = user ? user.id : null;

        setLoadingMessage("Uploading CV...");
        let cvUrl = null;
        if (cvFile) {
            cvUrl = await uploadCV(cvFile, formData.candidate_name);
        }

        setLoadingMessage("Finalizing application...");
        const refNumber = await generateReferenceNumber();
        const fullPhone = `${formData.countryCode}${formData.phone.replace(/^0+/, '')}`;

        const appPayload = {
            job_id: jobId,
            candidate_name: formData.candidate_name,
            email: formData.email,
            phone: fullPhone,
            description: formData.description,
            cv_url: cvUrl,
            reference_number: refNumber,
            status: 'pending',
            user_id: userId
        };
        
        const createdApp = await createApplication(appPayload);
        await incrementApplicantCount(jobId);
        
        const emailData = { ...createdApp, jobTitle: job.title };
        sendApplicationConfirmation(formData.email, emailData).catch(console.error);
        sendApplicationReceivedAdmin(null, emailData).catch(console.error);

        toast({ title: "Application Submitted", description: "Good luck! Confirmation email sent." });
        navigate(`/application-confirmation/${refNumber}`);

    } catch (error) {
        console.error(error);
        toast({ title: "Submission Failed", description: error.message, variant: "destructive" });
    } finally {
        setSubmitting(false);
    }
  };

  if (loading) return <div className="flex justify-center py-20"><Loader2 className="w-10 h-10 animate-spin text-[#003D82]" /></div>;

  if (error) return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
          <Card className="max-w-md w-full text-center p-8">
              <AlertCircle className="w-16 h-16 text-red-500 mx-auto mb-4" />
              <h2 className="text-2xl font-bold text-gray-800 mb-2">Unavailable</h2>
              <p className="text-gray-600 mb-6">{error}</p>
              <Button onClick={() => navigate('/apply-now')}>Back to Jobs</Button>
          </Card>
      </div>
  );

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4">
      <div className="max-w-4xl mx-auto">
        <Button variant="ghost" onClick={() => navigate('/apply-now')} className="mb-4 pl-0 hover:pl-2 transition-all">
            <ChevronLeft className="w-4 h-4 mr-1" /> Back to Jobs
        </Button>

        <Card className="border-t-4 border-t-[#003D82] shadow-xl overflow-hidden">
            <CardHeader className="bg-gray-50 border-b border-gray-100 pb-6">
                <div className="flex flex-col md:flex-row justify-between items-start gap-4">
                    <div className="space-y-2">
                        <CardTitle className="text-2xl md:text-3xl text-[#003D82]">{job.title}</CardTitle>
                        
                        {job.salary && (
                            <p className="text-lg font-semibold text-green-700 bg-green-50 inline-block px-3 py-1 rounded">
                                {formatSalary(job.salary)}
                            </p>
                        )}
                        
                        {remainingApps !== null && (
                            <p className="text-sm text-gray-500 flex items-center gap-1">
                                <Users className="w-4 h-4" /> {remainingApps} positions remaining
                            </p>
                        )}
                    </div>
                    
                    {/* Countdown Timer Display */}
                    {job.enable_countdown && job.deadline && (
                        <div className="bg-white p-4 rounded-lg shadow-sm border border-blue-100 min-w-[200px] text-center">
                            <p className="text-xs text-gray-500 font-bold uppercase tracking-wider mb-2">Latest Date for Application</p>
                            <div className="flex justify-center">
                                <CountdownTimerBox deadline={job.deadline} />
                            </div>
                        </div>
                    )}
                </div>

                <div className="mt-6 space-y-4">
                    <div>
                        <h4 className="font-semibold text-gray-800 mb-2">Description</h4>
                        <p className="text-gray-600 whitespace-pre-wrap leading-relaxed">{job.description}</p>
                    </div>

                    {job.min_requirements && job.min_requirements !== 'Not specified' && (
                        <div className="bg-blue-50 p-4 rounded-md border border-blue-100 text-sm">
                            <h4 className="font-semibold text-blue-800 mb-1 flex items-center gap-2">
                                <CheckCircle className="w-4 h-4" /> Requirements
                            </h4>
                            <p className="text-blue-700 whitespace-pre-wrap">{job.min_requirements}</p>
                        </div>
                    )}
                </div>
            </CardHeader>

            <CardContent className="pt-8">
                <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl mx-auto">
                    <h3 className="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Application Form</h3>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-2">
                            <Label htmlFor="candidate_name">Full Name <span className="text-red-500">*</span></Label>
                            <Input 
                                id="candidate_name" name="candidate_name" 
                                value={formData.candidate_name} onChange={handleChange} required 
                                placeholder="Engr. Mbole"
                                className="focus:border-[#003D82]"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">Phone Number <span className="text-red-500">*</span></Label>
                            <div className="flex gap-2">
                                <CountryCodeSelect 
                                    value={formData.countryCode}
                                    onChange={handleCountryChange}
                                />
                                <Input 
                                    id="phone" name="phone" 
                                    value={formData.phone} onChange={handleChange} required 
                                    placeholder="788 123 456"
                                    className="focus:border-[#003D82] flex-1"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email Address <span className="text-red-500">*</span></Label>
                        <Input 
                            id="email" name="email" type="email"
                            value={formData.email} onChange={handleChange} required 
                            placeholder="email@example.com"
                            className="focus:border-[#003D82] bg-gray-50"
                            readOnly={!!user && !!user.email} // Locked if logged in and has email
                        />
                        {user && <p className="text-xs text-green-600 flex items-center gap-1"><CheckCircle className="w-3 h-3" /> Verified Account</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Short Introduction / Cover Note</Label>
                        <Textarea 
                            id="description" name="description" 
                            value={formData.description} onChange={handleChange} 
                            placeholder="Tell us a bit about yourself..."
                            rows={5}
                            className="focus:border-[#003D82]"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="cv">Upload CV (PDF, Max 5MB) <span className="text-red-500">*</span></Label>
                        <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 flex flex-col items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer relative group">
                            <Input 
                                id="cv" type="file" accept="application/pdf"
                                onChange={handleFileChange} required
                                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                            />
                            <div className="bg-blue-50 p-3 rounded-full mb-3 group-hover:bg-blue-100 transition-colors">
                                <UploadCloud className="w-8 h-8 text-[#003D82]" />
                            </div>
                            <span className="text-sm font-medium text-gray-700">
                                {cvFile ? cvFile.name : "Click to upload your CV"}
                            </span>
                            <span className="text-xs text-gray-500 mt-1">PDF Format Only (Max 5MB)</span>
                        </div>
                    </div>

                    <Button type="submit" className="w-full bg-[#003D82] hover:bg-[#002d62] text-lg py-6 font-bold shadow-lg" disabled={submitting}>
                        {submitting ? (
                            <><Loader2 className="w-5 h-5 mr-2 animate-spin" /> {loadingMessage}</>
                        ) : (
                            "Submit Application"
                        )}
                    </Button>
                </form>
            </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default JobApplicationFormPage;