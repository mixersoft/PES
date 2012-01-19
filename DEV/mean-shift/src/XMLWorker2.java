import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

import org.xml.sax.Attributes;
import org.xml.sax.helpers.DefaultHandler;

public class XMLWorker2 extends DefaultHandler {
	protected String m_LastAudition = "";
	protected double m_LastDate = 0;
	protected String m_LastRawDate = "";
	protected double m_LastRating = 0;

	protected boolean m_InRating = false;

	protected DateFormat m_Format = new SimpleDateFormat("yyyy-MM-dd+HH:mm:ss");

	public ArrayList<double[]> Vectors = new ArrayList<double[]>();
	public ArrayList<Object[]> RawData = new ArrayList<Object[]>();
	
	public void startElement(String namespaceUri, String localName, String qualifiedName, Attributes attributes) {
		if(qualifiedName.equals("sn:Audition")) {
			m_LastAudition = attributes.getValue("id");
		} else if(qualifiedName.equals("sn:Photo")) {
			try {
				//String dt
				m_LastRawDate = attributes.getValue("DateTaken");
				m_LastRawDate = m_LastRawDate.replace('T', '+');
				Date dat = (Date)m_Format.parse(m_LastRawDate);
				//double ts
				m_LastDate = (double)dat.getTime() / 1000;
				//double vec[] = {ts};
				//Vectors.add(vec);
				//Object[] raw = {dt, m_LastAudition, (Double)ts};
				//RawData.add(raw);
			} catch(Exception e) {
				e.printStackTrace();
			}
			
		} else if(qualifiedName.equals("sn:Rating")) {
			m_InRating = true;	
		}
	}

	public void endElement(String namespaceUri, String localName, String qualifiedName) {
		if(qualifiedName.equals("sn:Rating")) {	
			m_InRating = false;	
		} else if(qualifiedName.equals("sn:Photo")) {
			double vec[] = {m_LastDate};
			Vectors.add(vec);
			Object[] raw = {m_LastRawDate, m_LastAudition, (Double)m_LastDate, m_LastRating};
			RawData.add(raw);
		}
	}
	
	public void characters(char[] ch, int start, int length) {
		if(m_InRating) {
			m_LastRating = Math.pow(3, Integer.parseInt(new String(ch, start, length))); 
		}
	}
}
