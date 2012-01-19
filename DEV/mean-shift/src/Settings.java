import java.util.Properties;
import java.io.*;

import org.apache.commons.cli.*;

public class Settings {
	protected Properties m_properties = new Properties();
	protected String m_ConfigName = "conf.cfg";

	public Settings(String[] args) {
		try {
			Options opt = new Options();

			opt.addOption("h", "help", false, "Print help for this application");
			opt.addOption("c", "config", true, "Name of configuration file. Default conf.cfg");
			opt.addOption("i", "input", true, "Name of input file. Default ex.xml");
			opt.addOption("o", "output", true, "Name of output file. Default out.xml");
			opt.addOption("dev", "devide-event-value", true, "Value for dividing to groups for event clustering");
			opt.addOption("dsv", "devide-seq-value", true, "Value for dividing to groups for sequence clustering");
			opt.addOption("b", "bandwidth", true, "Bandwidth for event clustering");
			opt.addOption("bs", "bandwidthForSequence", true, "Bandwidth forsequence clustering");
			opt.addOption("dec", "do-event-clustering", false, "Do event clustering");
			opt.addOption("dsc", "do-seq-clustering", false, "Do sequence clustering");
			opt.addOption("dcc", "do-chunk-clustering", false, "Do chunk clustering");
			opt.addOption("fev", "filter-event-value", true, "Value for filtering photos by score in event clustering");
			opt.addOption("gpr", "good-photo-rating", true, "Minimal rating value for 'good' photo");
			opt.addOption("ca", "chunk-algorithm", true, "Set algorithm for chunk clustering. Possible values are: '1' for k-means and '2' for k-medoids");
			opt.addOption("a", "append", false, "Add results to infile. Default outputs only results.");

			CommandLineParser parser = new GnuParser();
			CommandLine cl = parser.parse(opt, args);

			if(cl.hasOption('h')) {
				HelpFormatter f = new HelpFormatter();
				f.printHelp("mean-shift", opt);
				System.exit(0);
			} 
			
			if(cl.hasOption('c')) m_ConfigName = cl.getOptionValue('c'); 
			System.out.println("Loading config - " + m_ConfigName);
			try {
				m_properties.load(new FileInputStream(m_ConfigName));
			} catch (IOException e) {
				e.printStackTrace();
				System.exit(0);
			}
			
			if(cl.hasOption("output")) m_properties.setProperty("output", cl.getOptionValue("o"));
			else m_properties.setProperty("output", "out.xml");
			if(cl.hasOption("input")) m_properties.setProperty("input", cl.getOptionValue("i"));
			else m_properties.setProperty("input", "ex.xml");
			if(cl.hasOption("dev")) m_properties.setProperty("divideEventValue", cl.getOptionValue("dev"));
			if(cl.hasOption("dsv")) m_properties.setProperty("divideSequenceValue", cl.getOptionValue("dsv"));
			if(cl.hasOption("b")) m_properties.setProperty("bandwidth", cl.getOptionValue("b"));
			if(cl.hasOption("bs")) m_properties.setProperty("bandwidthForSequence", cl.getOptionValue("bs"));
			if(cl.hasOption("dec")) m_properties.setProperty("doClustering", "1.0");
			if(cl.hasOption("dsc")) m_properties.setProperty("doSequenceCl", "1.0");
			if(cl.hasOption("dcc")) m_properties.setProperty("doChunkCl", "1.0");
			if(cl.hasOption("fev")) m_properties.setProperty("filterEventValue", cl.getOptionValue("fev"));
			if(cl.hasOption("gpr")) m_properties.setProperty("goodPhotoRating", cl.getOptionValue("gpr"));
			if(cl.hasOption("ca")) m_properties.setProperty("chunkAlgo", cl.getOptionValue("ca"));
			if(cl.hasOption("a")) m_properties.setProperty("append", "1.0");
			
			// clustering/sequence(aubstitutions)
			m_properties.list(new PrintWriter(System.out, true));
		} catch (ParseException e) {
			e.printStackTrace();
		}
	}

	public double getParam(String nm) {
//		System.out.println(">>> get param: " + nm);
		String prop = m_properties.getProperty(nm);
		return Double.parseDouble(prop);
	}

	public String getParamStr(String nm) {
		String prop = m_properties.getProperty(nm);
		return prop;
	}
}
