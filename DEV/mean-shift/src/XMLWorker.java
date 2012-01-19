import java.io.File;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.OutputKeys;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;

import org.w3c.dom.Attr;
import org.w3c.dom.DOMImplementation;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

public class XMLWorker {
	public ArrayList<double[]> Vectors = new ArrayList<double[]>();
	public ArrayList<Object[]> RawData = new ArrayList<Object[]>();
	
	protected Document m_Document = null;
	
	public void parse(String fn) {
		try {
			File file = new File(fn);
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			DocumentBuilder db = dbf.newDocumentBuilder();
			m_Document = db.parse(file);
			m_Document.getDocumentElement().normalize();
			NodeList nodeLst = m_Document.getElementsByTagName("sn:Audition");
			DateFormat dt_format = new SimpleDateFormat("yyyy-MM-dd+HH:mm:ss");

			for(int i = 0; i < nodeLst.getLength(); i++) {
				Node aud = nodeLst.item(i);
				String aud_id = (String)aud.getAttributes().getNamedItem("id").getNodeValue();
				//System.out.println(aud.getAttributes().getNamedItem("id"));	
				if(aud.getNodeType() == Node.ELEMENT_NODE) {
					NodeList photos = ((Element)aud).getElementsByTagName("sn:Photo");
					for(int j = 0; j < photos.getLength(); j++) {
						Node photo = photos.item(j);
						String dt = photo.getAttributes().getNamedItem("DateTaken").getNodeValue();
						dt = dt.replace('T', '+');
						Date dat = (Date)dt_format.parse(dt);
						//System.out.println(dat.getTime() / 1000);	
						double ts = (double)dat.getTime() / 1000;
						double vec[] = {ts};
						Vectors.add(vec);
						Object[] raw = {dt, aud_id, (Double)ts};
						RawData.add(raw);
					}
				}
			}  	
		} catch (Exception e) {
			e.printStackTrace();
		}
  		
	} 

	protected void iterateOverVectors(Cluster cls, Element sx) {
		for(int i = 0; i < cls.Items.size(); i++) {
			Element it_el = m_Document.createElement("sn:AuditionREF");
			NamedNodeMap attrs = it_el.getAttributes();
			Attr id = m_Document.createAttribute("idref");
			id.setValue((String)cls.Items.get(i).RawVector[1]);
			attrs.setNamedItem(id);
			Attr score = m_Document.createAttribute("score");
			score.setValue(Double.toString(cls.Items.get(i).Score));
			attrs.setNamedItem(score);
			
			sx.appendChild(it_el);
		}
	}
	
	public void saveClusters(String fn, ArrayList<Cluster> sequ, ArrayList<Cluster> events) {
		Node clus = null;
		if(m_Document == null) {
			try {
				DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
				DocumentBuilder builder = factory.newDocumentBuilder();
				DOMImplementation impl = builder.getDOMImplementation();
				m_Document = impl.createDocument(null,null,null);
				clus = m_Document.createElement("sn:Clusters");

				NamedNodeMap attrs = clus.getAttributes();
				Attr ns = m_Document.createAttribute("xmlns:sn");
				ns.setValue("snaphappi.com");
				attrs.setNamedItem(ns);

				ns = m_Document.createAttribute("xmlns:xsi");
				ns.setValue("'http://www.w3.org/2001/XMLSchema-instance");
				attrs.setNamedItem(ns);
				
				m_Document.appendChild(clus);
			} catch(Exception e) {
				e.printStackTrace();
				return ;
			}
		} else {
			NodeList chld = m_Document.getChildNodes().item(0).getChildNodes();
			for(int i = 0; i < chld.getLength(); i++) {	
				Node nod = chld.item(i);
				try {
					if(nod.getNodeName().compareTo("sn:Clusters") == 0) clus = nod;
					System.out.println(nod.getNodeName());
				} catch (Exception e) {}		
			}
		}
		
		for(int i = 0; i < sequ.size(); i++) {
			Element sx = m_Document.createElement("sn:Substitution");
			NamedNodeMap attrs = sx.getAttributes();
			Attr type = m_Document.createAttribute("Type");
			type.setValue("Substitution");
			attrs.setNamedItem(type);
			Attr id = m_Document.createAttribute("id");
			id.setValue("snappi-substitute-" + Integer.toString(i));
			attrs.setNamedItem(id);
			Attr score = m_Document.createAttribute("score");
			score.setValue(Double.toString(sequ.get(i).Score));
			attrs.setNamedItem(score);
			iterateOverVectors(sequ.get(i), sx);
			clus.appendChild(sx);
		}

		for(int i = 0; i < events.size(); i++) {
			Element sx = m_Document.createElement("sn:Cluster");
			NamedNodeMap attrs = sx.getAttributes();
			Attr type = m_Document.createAttribute("Type");
			type.setValue("event");
			attrs.setNamedItem(type);
			Attr id = m_Document.createAttribute("id");
			id.setValue("snappi-cluster-" + Integer.toString(i));
			attrs.setNamedItem(id);
			Attr score = m_Document.createAttribute("score");
			score.setValue(Double.toString(events.get(i).Score));
			attrs.setNamedItem(score);
			iterateOverVectors(events.get(i), sx);
			clus.appendChild(sx);
		}

		try {
			TransformerFactory tFactory = TransformerFactory.newInstance();
			Transformer transformer = tFactory.newTransformer();
			transformer.setOutputProperty(OutputKeys.INDENT, "yes");
			DOMSource source = new DOMSource(m_Document);
			StreamResult result = new StreamResult(new File(fn));
			transformer.transform(source, result); 
		} catch (Exception e) {
			e.printStackTrace();
		}
	}	
}
